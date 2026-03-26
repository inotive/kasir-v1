<?php

namespace App\Services\Transactions;

use App\Models\Transaction;
use App\Models\TransactionEvent;

class TransactionEventService
{
    public function record(Transaction $transaction, string $action, array $meta = [], ?int $actorUserId = null): void
    {
        TransactionEvent::query()->create([
            'transaction_id' => (int) $transaction->id,
            'actor_user_id' => $actorUserId,
            'action' => $action,
            'meta' => $meta === [] ? null : $meta,
        ]);
    }
}
