<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewMidtransTransaction implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $transactionId;

    public string $code;

    public string $table;

    /**
     * Create a new event instance.
     */
    public function __construct($transactionId, string $code = '', string $table = '')
    {
        $this->transactionId = $transactionId;
        $this->code = $code;
        $this->table = $table;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('cashier.orders'),
        ];
    }

    public function broadcastAs()
    {
        return 'midtrans.paid';
    }
}
