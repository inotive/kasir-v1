<?php

namespace App\Livewire\Transaction;

use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use App\Services\Printing\PosPrintPayloadService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class TransactionShowPage extends Component
{
    public string $title = 'Detail Transaksi';

    public int $transactionId;

    public bool $voidModalOpen = false;

    public bool $refundModalOpen = false;

    public string $correctionReason = '';

    public bool $revertInventory = false;

    public int $refundAmount = 0;

    public ?int $approverUserId = null;

    public string $approverPin = '';

    private function resolveApprover(?int $approverUserId, string $pin, string $approvalPermission): array
    {
        $pin = trim($pin);
        if ($pin === '') {
            return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverPin', 'error_message' => 'PIN wajib diisi.'];
        }

        if (! preg_match('/^\d{4,8}$/', $pin)) {
            return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverPin', 'error_message' => 'Format PIN harus 4-8 digit.'];
        }

        if ($approverUserId) {
            $approver = User::query()
                ->whereKey($approverUserId)
                ->where('is_active', true)
                ->first();

            if (! $approver || ! $approver->can($approvalPermission) || ! $approver->manager_pin) {
                return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverUserId', 'error_message' => 'Akun approver tidak valid atau belum punya PIN.'];
            }

            $stored = (string) $approver->manager_pin;
            if (! str_starts_with($stored, '$')) {
                if (! hash_equals($stored, $pin)) {
                    return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverPin', 'error_message' => 'PIN salah.'];
                }
                $approver->manager_pin = $pin;
                $approver->manager_pin_set_at = now();
                $approver->save();
            } else {
                try {
                    if (! Hash::check($pin, $stored)) {
                        return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverPin', 'error_message' => 'PIN salah.'];
                    }
                } catch (\Throwable) {
                    return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverUserId', 'error_message' => 'PIN approver perlu diset ulang.'];
                }
            }

            return ['ok' => true, 'id' => (int) $approver->id, 'mode' => 'selected', 'error_field' => null, 'error_message' => null];
        }

        $candidates = User::query()
            ->permission($approvalPermission)
            ->where('is_active', true)
            ->whereNotNull('manager_pin')
            ->get(['id', 'manager_pin']);

        $matches = [];
        foreach ($candidates as $candidate) {
            $stored = (string) $candidate->manager_pin;
            if (! str_starts_with($stored, '$')) {
                if (hash_equals($stored, $pin)) {
                    $candidate->manager_pin = $pin;
                    $candidate->manager_pin_set_at = now();
                    $candidate->save();
                    $matches[] = (int) $candidate->id;
                }

                continue;
            }

            try {
                if (Hash::check($pin, $stored)) {
                    $matches[] = (int) $candidate->id;
                }
            } catch (\Throwable) {
                continue;
            }
        }

        if (count($matches) === 1) {
            return ['ok' => true, 'id' => $matches[0], 'mode' => 'pin_match', 'error_field' => null, 'error_message' => null];
        }

        if (count($matches) > 1) {
            return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverUserId', 'error_message' => 'PIN cocok ke lebih dari 1 approver. Silakan pilih approver.'];
        }

        return ['ok' => false, 'id' => null, 'mode' => null, 'error_field' => 'approverPin', 'error_message' => 'PIN salah.'];
    }

    private function needsVoidApproval(User $actor, Transaction $transaction, Setting $setting, int $quickUsedToday): bool
    {
        $needsApproval = (bool) ($setting->corrections_void_pending_requires_approval ?? false);
        if ($needsApproval) {
            return true;
        }

        if ($actor->can('transactions.void.approve')) {
            return false;
        }

        $windowMinutes = max(0, (int) ($setting->corrections_void_quick_window_minutes ?? 0));
        if ($windowMinutes > 0 && $transaction->created_at) {
            $cutoff = now()->subMinutes($windowMinutes);
            if ($transaction->created_at->lt($cutoff)) {
                return true;
            }
        }

        $maxCount = max(0, (int) ($setting->corrections_void_quick_max_count_per_day ?? 0));
        if ($maxCount === 0) {
            return true;
        }

        return (int) $quickUsedToday >= $maxCount;
    }

    private function needsRefundApproval(User $actor, Transaction $transaction, Setting $setting, int $refundAmount, int $quickUsedToday): bool
    {
        $quickMaxAmount = max(0, (int) ($setting->corrections_refund_quick_max_amount ?? 0));
        $quickMaxCount = max(0, (int) ($setting->corrections_refund_quick_max_count_per_day ?? 0));
        $requireForCash = (bool) ($setting->corrections_refund_requires_approval_for_cash ?? true);

        if ($quickMaxCount === 0) {
            return true;
        }

        if ((int) $refundAmount > $quickMaxAmount) {
            return true;
        }

        if ($requireForCash && (string) $transaction->payment_method === 'cash') {
            return true;
        }

        return (int) $quickUsedToday >= $quickMaxCount;
    }

    private function buildPrintPayload(int $transactionId): ?array
    {
        return app(PosPrintPayloadService::class)->build($transactionId);
    }

    public function printReceipt(): void
    {
        $actor = auth()->user();
        if (! $actor || ! $actor->can('transactions.print')) {
            $this->dispatch('toast', type: 'error', message: 'Anda tidak punya akses untuk mencetak struk.');

            return;
        }

        $payload = $this->buildPrintPayload($this->transactionId);
        if (! $payload) {
            $this->dispatch('toast', type: 'error', message: 'Transaksi tidak ditemukan.');

            return;
        }

        $this->dispatch('pos-print-modal', payload: $payload, context: 'transaction_detail');
    }

    public function processInventory(InventoryService $inventory): void
    {
        $this->authorize('inventory.manage');

        $transaction = Transaction::query()
            ->with(['transactionItems.variant.recipes.ingredient', 'transactionItems.product.recipes.ingredient'])
            ->findOrFail($this->transactionId);

        try {
            $inventory->applyTransaction($transaction);
        } catch (ValidationException $e) {
            $message = (string) ($e->errors()['inventory'][0] ?? 'Gagal memproses inventory.');
            $this->addError('inventory', $message);
        }
    }

    public function openVoidModal(): void
    {
        $this->authorize('transactions.void');

        $this->correctionReason = '';
        $this->refundAmount = 0;
        $this->revertInventory = false;
        $this->approverUserId = null;
        $this->approverPin = '';
        $this->resetValidation();
        $this->voidModalOpen = true;
    }

    public function closeVoidModal(): void
    {
        $this->voidModalOpen = false;
        $this->resetValidation();
    }

    public function openRefundModal(): void
    {
        $this->authorize('transactions.refund');

        $this->correctionReason = '';
        $this->revertInventory = false;
        $this->refundAmount = 0;
        $this->approverUserId = null;
        $this->approverPin = '';
        $this->resetValidation();
        $this->refundModalOpen = true;
    }

    public function closeRefundModal(): void
    {
        $this->refundModalOpen = false;
        $this->resetValidation();
    }

    public function voidTransaction(InventoryService $inventory): void
    {
        $this->resetErrorBag();

        $validated = $this->validate([
            'correctionReason' => ['required', 'string', 'max:255'],
            'revertInventory' => ['boolean'],
            'approverUserId' => ['nullable', 'integer'],
            'approverPin' => ['nullable', 'string', 'max:20'],
        ]);

        $success = false;

        DB::transaction(function () use ($validated, $inventory, &$success): void {
            $actor = auth()->user();
            if (! $actor || ! $actor->can('transactions.void')) {
                $this->addError('correctionReason', 'Anda tidak punya akses untuk void transaksi.');

                return;
            }

            $transaction = Transaction::query()
                ->lockForUpdate()
                ->with(['transactionItems'])
                ->findOrFail($this->transactionId);

            if ((string) $transaction->payment_status !== 'pending') {
                $this->addError('correctionReason', 'Void hanya bisa untuk transaksi yang masih pending.');

                return;
            }

            if (in_array((string) $transaction->payment_status, ['voided', 'refunded'], true)) {
                $this->addError('correctionReason', 'Transaksi sudah dikoreksi.');

                return;
            }

            $setting = Setting::current();
            $quickUsedToday = TransactionEvent::query()
                ->where('action', 'void')
                ->where('actor_user_id', auth()->id())
                ->whereDate('created_at', now()->toDateString())
                ->whereNull('meta->approved_by_user_id')
                ->count();

            $needsApproval = $this->needsVoidApproval($actor, $transaction, $setting, (int) $quickUsedToday);
            $approvedByUserId = null;
            $approvalMode = null;

            if ($needsApproval) {
                $resolved = $this->resolveApprover($validated['approverUserId'] ?? null, (string) ($validated['approverPin'] ?? ''), 'transactions.void.approve');
                if (! $resolved['ok']) {
                    $this->addError((string) $resolved['error_field'], (string) $resolved['error_message']);

                    return;
                }

                $approvedByUserId = (int) $resolved['id'];
                $approvalMode = (string) $resolved['mode'];
            }

            $transaction->forceFill([
                'payment_status' => 'voided',
                'voided_at' => now(),
                'voided_by_user_id' => auth()->id(),
                'void_reason' => $validated['correctionReason'],
            ])->save();

            TransactionEvent::query()->create([
                'transaction_id' => $transaction->id,
                'actor_user_id' => auth()->id(),
                'action' => 'void',
                'meta' => [
                    'reason' => $validated['correctionReason'],
                    'revert_inventory' => (bool) $validated['revertInventory'],
                    'approval_required' => $needsApproval,
                    'approved_by_user_id' => $approvedByUserId,
                    'approval_mode' => $approvalMode,
                    'previous_payment_status' => 'pending',
                    'new_payment_status' => 'voided',
                ],
            ]);

            if ($transaction->inventory_applied_at && (bool) $validated['revertInventory']) {
                $inventory->reverseTransaction($transaction, 'Reversal void transaksi '.$transaction->code);
                TransactionEvent::query()->create([
                    'transaction_id' => $transaction->id,
                    'actor_user_id' => auth()->id(),
                    'action' => 'inventory_reverse',
                    'meta' => [
                        'reason' => $validated['correctionReason'],
                        'source' => 'void',
                    ],
                ]);
            }

            $success = true;
        });

        if ($success) {
            $this->closeVoidModal();
            $this->dispatch('toast', type: 'success', message: 'Transaksi berhasil di-void.');
        }
    }

    public function refundTransaction(InventoryService $inventory): void
    {
        $this->resetErrorBag();

        $validated = $this->validate([
            'correctionReason' => ['required', 'string', 'max:255'],
            'refundAmount' => ['required', 'integer', 'min:1'],
            'revertInventory' => ['boolean'],
            'approverUserId' => ['nullable', 'integer'],
            'approverPin' => ['nullable', 'string', 'max:20'],
        ]);

        $success = false;

        DB::transaction(function () use ($validated, $inventory, &$success): void {
            $actor = auth()->user();
            if (! $actor || ! $actor->can('transactions.refund')) {
                $this->addError('refundAmount', 'Anda tidak punya akses untuk refund transaksi.');

                return;
            }

            $transaction = Transaction::query()
                ->lockForUpdate()
                ->with(['transactionItems'])
                ->findOrFail($this->transactionId);

            if (! in_array((string) $transaction->payment_status, ['paid', 'partial_refund'], true)) {
                $this->addError('refundAmount', 'Refund hanya bisa untuk transaksi yang sudah paid.');

                return;
            }

            $previousStatus = (string) $transaction->payment_status;
            $total = (int) $transaction->total;
            $refunded = (int) ($transaction->refunded_amount ?? 0);
            $remaining = max(0, $total - $refunded);

            if ((int) $validated['refundAmount'] > $remaining) {
                $this->addError('refundAmount', 'Nominal refund melebihi sisa total transaksi.');

                return;
            }

            $newRefunded = $refunded + (int) $validated['refundAmount'];
            $newStatus = $newRefunded >= $total ? 'refunded' : 'partial_refund';

            $setting = Setting::current();
            $quickUsedToday = TransactionEvent::query()
                ->where('action', 'refund')
                ->where('actor_user_id', auth()->id())
                ->whereDate('created_at', now()->toDateString())
                ->whereNull('meta->approved_by_user_id')
                ->count();

            $needsApproval = $this->needsRefundApproval($actor, $transaction, $setting, (int) $validated['refundAmount'], (int) $quickUsedToday);

            $approvedByUserId = null;
            $approvalMode = null;
            if ($needsApproval) {
                $resolved = $this->resolveApprover($validated['approverUserId'] ?? null, (string) ($validated['approverPin'] ?? ''), 'transactions.refund.approve');
                if (! $resolved['ok']) {
                    $this->addError((string) $resolved['error_field'], (string) $resolved['error_message']);

                    return;
                }

                $approvedByUserId = (int) $resolved['id'];
                $approvalMode = (string) $resolved['mode'];
            }

            $transaction->forceFill([
                'refunded_amount' => $newRefunded,
                'refunded_at' => now(),
                'refunded_by_user_id' => auth()->id(),
                'refund_reason' => $validated['correctionReason'],
                'payment_status' => $newStatus,
            ])->save();

            TransactionEvent::query()->create([
                'transaction_id' => $transaction->id,
                'actor_user_id' => auth()->id(),
                'action' => 'refund',
                'meta' => [
                    'reason' => $validated['correctionReason'],
                    'amount' => (int) $validated['refundAmount'],
                    'refunded_total' => $newRefunded,
                    'revert_inventory' => (bool) $validated['revertInventory'],
                    'approval_required' => $needsApproval,
                    'approved_by_user_id' => $approvedByUserId,
                    'approval_mode' => $approvalMode,
                    'previous_payment_status' => $previousStatus,
                    'new_payment_status' => $newStatus,
                ],
            ]);

            if ($transaction->inventory_applied_at && (bool) $validated['revertInventory']) {
                $inventory->reverseTransaction($transaction, 'Reversal refund transaksi '.$transaction->code);
                TransactionEvent::query()->create([
                    'transaction_id' => $transaction->id,
                    'actor_user_id' => auth()->id(),
                    'action' => 'inventory_reverse',
                    'meta' => [
                        'reason' => $validated['correctionReason'],
                        'source' => 'refund',
                    ],
                ]);
            }

            $success = true;
        });

        if ($success) {
            $this->closeRefundModal();
            $this->dispatch('toast', type: 'success', message: 'Refund berhasil dicatat.');
        }
    }

    public function mount(Transaction $transaction): void
    {
        $this->authorize('transactions.details');

        $this->transactionId = (int) $transaction->id;
        $this->title = 'Detail Transaksi - '.$transaction->code;
    }

    public function render(): View
    {
        $this->authorize('transactions.details');

        $rules = Setting::current();

        $transaction = Transaction::query()
            ->with([
                'member',
                'diningTable',
                'voucherCampaign',
                'voucherCode',
                'manualDiscountByUser',
                'transactionItems.product',
                'transactionItems.variant',
                'events' => fn ($q) => $q->latest(),
                'events.actor',
            ])
            ->findOrFail($this->transactionId);

        $user = auth()->user();
        $voidQuickUsedToday = TransactionEvent::query()
            ->where('action', 'void')
            ->where('actor_user_id', auth()->id())
            ->whereDate('created_at', now()->toDateString())
            ->whereNull('meta->approved_by_user_id')
            ->count();

        $refundQuickUsedToday = TransactionEvent::query()
            ->where('action', 'refund')
            ->where('actor_user_id', auth()->id())
            ->whereDate('created_at', now()->toDateString())
            ->whereNull('meta->approved_by_user_id')
            ->count();

        $voidNeedsApproval = $user ? $this->needsVoidApproval($user, $transaction, $rules, (int) $voidQuickUsedToday) : false;
        $refundNeedsApproval = $user ? $this->needsRefundApproval($user, $transaction, $rules, (int) $this->refundAmount, (int) $refundQuickUsedToday) : false;

        $voidApprovers = collect();
        if ($this->voidModalOpen && $voidNeedsApproval && $user && $user->can('transactions.void')) {
            $voidApprovers = User::query()
                ->permission('transactions.void.approve')
                ->where('is_active', true)
                ->whereNotNull('manager_pin')
                ->orderBy('name')
                ->get(['id', 'name', 'manager_pin_set_at']);
        }

        $refundApprovers = collect();
        if ($this->refundModalOpen && $refundNeedsApproval && $user && $user->can('transactions.refund')) {
            $refundApprovers = User::query()
                ->permission('transactions.refund.approve')
                ->where('is_active', true)
                ->whereNotNull('manager_pin')
                ->orderBy('name')
                ->get(['id', 'name', 'manager_pin_set_at']);
        }

        $approvedIds = collect($transaction->events)
            ->map(fn ($e) => $e->meta['approved_by_user_id'] ?? null)
            ->filter()
            ->unique()
            ->values();

        $approvedBy = $approvedIds->isEmpty()
            ? []
            : User::query()
                ->whereIn('id', $approvedIds->all())
                ->get(['id', 'name'])
                ->mapWithKeys(fn (User $u) => [(int) $u->id => (string) $u->name])
                ->all();

        return view('livewire.transactions.transaction-show-page', [
            'transaction' => $transaction,
            'voidApprovers' => $voidApprovers,
            'refundApprovers' => $refundApprovers,
            'approvedBy' => $approvedBy,
            'refundQuickUsedToday' => (int) $refundQuickUsedToday,
            'voidQuickUsedToday' => (int) $voidQuickUsedToday,
            'voidNeedsApproval' => (bool) $voidNeedsApproval,
            'refundNeedsApproval' => (bool) $refundNeedsApproval,
            'correctionRules' => [
                'void_pending_requires_approval' => (bool) ($rules->corrections_void_pending_requires_approval ?? false),
                'void_quick_max_count_per_day' => (int) ($rules->corrections_void_quick_max_count_per_day ?? 0),
                'void_quick_window_minutes' => (int) ($rules->corrections_void_quick_window_minutes ?? 0),
                'refund_requires_approval_for_cash' => (bool) ($rules->corrections_refund_requires_approval_for_cash ?? true),
                'refund_quick_max_amount' => (int) ($rules->corrections_refund_quick_max_amount ?? 0),
                'refund_quick_max_count_per_day' => (int) ($rules->corrections_refund_quick_max_count_per_day ?? 0),
                'approver_permission_void' => 'transactions.void.approve',
                'approver_permission_refund' => 'transactions.refund.approve',
            ],
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
