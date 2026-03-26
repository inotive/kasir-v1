<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SelfOrderCashPendingCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public int $transactionId;

    public string $code;

    public string $table;

    public int $total;

    public function __construct(int $transactionId, string $code, string $table, int $total)
    {
        $this->transactionId = $transactionId;
        $this->code = $code;
        $this->table = $table;
        $this->total = $total;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier.orders'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'self_order.cash_pending';
    }
}
