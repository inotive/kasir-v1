<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Models\Transaction;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Session;
use Livewire\Attributes\Layout;
use Livewire\Component;

class PaymentStatusPage extends Component
{
    public Transaction $transaction;

    public string $status = 'pending';

    public function getListeners(): array
    {
        $token = (string) ($this->transaction->self_order_token ?? '');

        if ($token === '') {
            return [];
        }

        return [
            "echo:self-order.{$token},.payment.updated" => 'handlePaymentUpdated',
        ];
    }

    public function mount(?string $code = null): void
    {
        $orderId = trim((string) request()->query('order_id', ''));

        $query = Transaction::query()
            ->where('channel', 'self_order')
            ->with(['transactionItems.product', 'transactionItems.variant', 'diningTable']);

        if ($orderId !== '') {
            $query->where('external_id', $orderId);
        } elseif ($code !== null && trim($code) !== '') {
            $query->where('code', trim($code));
        } else {
            abort(404);
        }

        $this->transaction = $query->firstOrFail();

        $this->status = (string) $this->transaction->payment_status;
        $this->syncSessionWithStatus();
    }

    public function handlePaymentUpdated(array $payload = []): void
    {
        $code = (string) ($payload['code'] ?? '');
        if ($code !== '' && $code !== (string) $this->transaction->code) {
            return;
        }

        $this->refreshFromDb();
    }

    private function refreshFromDb(): void
    {
        $this->transaction->refresh();
        $this->status = (string) $this->transaction->payment_status;
        $this->syncSessionWithStatus();
    }

    private function syncSessionWithStatus(): void
    {
        $status = strtolower((string) $this->status);

        if (in_array($status, ['paid', 'settled'], true)) {
            Session::forget(['external_id', 'has_unpaid_transaction', 'cart_items', 'payment_token', 'self_order_voucher_code', 'self_order_use_points', 'self_order_points_to_redeem']);
            Session::save();

            return;
        }

        if (in_array($status, ['failed', 'expired', 'canceled'], true)) {
            Session::forget(['external_id', 'has_unpaid_transaction', 'payment_token', 'self_order_use_points', 'self_order_points_to_redeem']);
            Session::save();
        }
    }

    #[Layout('layouts.self-order')]
    public function render(): View
    {
        return view('livewire.self-order.payment.status');
    }
}
