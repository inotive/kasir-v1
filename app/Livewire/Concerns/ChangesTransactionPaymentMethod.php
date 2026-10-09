<?php

namespace App\Livewire\Concerns;

use App\Models\Transaction;
use App\Models\TransactionEvent;
use Illuminate\Support\Facades\DB;

/**
 * Aksi "Ubah Metode Bayar" yang dipakai di list dan detail transaksi.
 * View memakai partial livewire.transactions.partials.payment-method-modal.
 */
trait ChangesTransactionPaymentMethod
{
    public bool $paymentMethodModalOpen = false;

    public ?int $paymentMethodTransactionId = null;

    public string $newPaymentMethod = '';

    public string $newCashReceived = '';

    public string $paymentMethodReason = '';

    /**
     * Metode bayar yang bisa dipilih saat koreksi (sama dengan opsi POS + transfer).
     *
     * @return array<string, string>
     */
    protected function changeablePaymentMethods(): array
    {
        return [
            'cash' => 'Tunai',
            'qris' => 'QRIS',
            'transfer_bank' => 'Transfer Bank',
        ];
    }

    protected function canChangePaymentMethod(Transaction $transaction): bool
    {
        // Transaksi gateway (Midtrans) sudah diverifikasi pihak ketiga dan
        // membawa biaya admin, jadi metodenya tidak boleh dikoreksi manual.
        return (string) $transaction->payment_status === 'paid'
            && (string) $transaction->payment_method !== 'qris_midtrans'
            && (int) ($transaction->payment_fee_amount ?? 0) === 0;
    }

    public function openPaymentMethodModal(int $transactionId): void
    {
        $this->authorize('transactions.payment_method.change');

        $this->paymentMethodTransactionId = $transactionId;
        $this->paymentMethodReason = '';
        $this->newPaymentMethod = '';
        $this->newCashReceived = '';
        $this->resetValidation();
        $this->paymentMethodModalOpen = true;
    }

    public function closePaymentMethodModal(): void
    {
        $this->paymentMethodModalOpen = false;
        $this->paymentMethodTransactionId = null;
        $this->resetValidation();
    }

    public function changePaymentMethod(): void
    {
        $this->resetErrorBag();

        $validated = $this->validate([
            'paymentMethodTransactionId' => ['required', 'integer'],
            'paymentMethodReason' => ['required', 'string', 'max:255'],
            'newPaymentMethod' => ['required', 'string', 'in:'.implode(',', array_keys($this->changeablePaymentMethods()))],
            'newCashReceived' => ['nullable', 'string', 'max:20'],
        ]);

        $success = false;

        DB::transaction(function () use ($validated, &$success): void {
            $actor = auth()->user();
            if (! $actor || ! $actor->can('transactions.payment_method.change')) {
                $this->addError('newPaymentMethod', 'Anda tidak punya akses untuk mengubah metode bayar.');

                return;
            }

            $transaction = Transaction::query()
                ->lockForUpdate()
                ->findOrFail((int) $validated['paymentMethodTransactionId']);

            if (! $this->canChangePaymentMethod($transaction)) {
                $this->addError('newPaymentMethod', 'Metode bayar hanya bisa diubah untuk transaksi paid non-gateway yang belum direfund.');

                return;
            }

            $previousMethod = (string) $transaction->payment_method;
            $newMethod = (string) $validated['newPaymentMethod'];
            if ($newMethod === $previousMethod) {
                $this->addError('newPaymentMethod', 'Metode bayar baru sama dengan metode saat ini.');

                return;
            }

            $total = (int) $transaction->total;
            $cashReceived = null;
            $cashChange = null;
            if ($newMethod === 'cash') {
                $digits = preg_replace('/\D+/', '', (string) ($validated['newCashReceived'] ?? ''));
                $cashReceived = $digits === '' ? null : (int) $digits;
                if ($cashReceived === null || $cashReceived < $total) {
                    $this->addError('newCashReceived', 'Uang diterima kurang dari total.');

                    return;
                }
                $cashChange = $cashReceived - $total;
            }

            $previousCashReceived = $transaction->cash_received;
            $previousCashChange = $transaction->cash_change;

            $transaction->forceFill([
                'payment_method' => $newMethod,
                'cash_received' => $cashReceived,
                'cash_change' => $cashChange,
            ])->save();

            TransactionEvent::query()->create([
                'transaction_id' => $transaction->id,
                'actor_user_id' => auth()->id(),
                'action' => 'payment_method_change',
                'meta' => [
                    'reason' => $validated['paymentMethodReason'],
                    'previous_payment_method' => $previousMethod,
                    'new_payment_method' => $newMethod,
                    'previous_cash_received' => $previousCashReceived,
                    'previous_cash_change' => $previousCashChange,
                    'new_cash_received' => $cashReceived,
                    'new_cash_change' => $cashChange,
                ],
            ]);

            $success = true;
        });

        if ($success) {
            $this->closePaymentMethodModal();
            $this->dispatch('toast', type: 'success', message: 'Metode bayar berhasil diubah.');
        }
    }

    private function paymentMethodModalTransaction(): ?Transaction
    {
        if (! $this->paymentMethodModalOpen || ! $this->paymentMethodTransactionId) {
            return null;
        }

        return Transaction::query()->find($this->paymentMethodTransactionId);
    }
}
