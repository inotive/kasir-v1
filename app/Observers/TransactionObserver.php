<?php

namespace App\Observers;

use App\Models\Transaction;
use App\Services\Inventory\InventoryService;
use App\Services\PointService;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class TransactionObserver
{
    public function saved(Transaction $transaction): void
    {
        if ($transaction->inventory_applied_at) {
            return;
        }

        $paidStatuses = ['paid', 'settlement', 'capture', 'success'];

        if (! in_array((string) $transaction->payment_status, $paidStatuses, true)) {
            return;
        }

        try {
            $skipped = app(InventoryService::class)->applyTransaction($transaction);

            if ($skipped !== []) {
                Log::warning('Inventory applied with skipped items (no recipes).', [
                    'transaction_id' => (int) $transaction->id,
                    'skipped' => $skipped,
                ]);
            }
        } catch (ValidationException) {
        }

        app(PointService::class)->awardPoints($transaction);
    }
}
