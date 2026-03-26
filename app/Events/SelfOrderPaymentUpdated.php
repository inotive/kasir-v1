<?php

namespace App\Events;

use App\Models\Transaction;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SelfOrderPaymentUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Transaction $transaction) {}

    public function broadcastOn(): array
    {
        $token = (string) ($this->transaction->self_order_token ?? '');

        return $token !== '' ? [new Channel('self-order.'.$token)] : [];
    }

    public function broadcastAs(): string
    {
        return 'payment.updated';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => (int) $this->transaction->id,
            'code' => (string) $this->transaction->code,
            'status' => (string) $this->transaction->payment_status,
            'method' => (string) $this->transaction->payment_method,
        ];
    }
}
