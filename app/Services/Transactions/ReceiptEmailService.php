<?php

namespace App\Services\Transactions;

use App\Mail\ReceiptMail;
use App\Models\Transaction;
use Illuminate\Support\Facades\Mail;

class ReceiptEmailService
{
    public function queueIfNeeded(Transaction $transaction): bool
    {
        $email = trim((string) ($transaction->email ?? ''));
        if ($email === '') {
            return false;
        }

        if ($transaction->receipt_emailed_at !== null) {
            return false;
        }

        $updated = Transaction::query()
            ->whereKey($transaction->id)
            ->whereNull('receipt_emailed_at')
            ->update(['receipt_emailed_at' => now()]);

        if ($updated !== 1) {
            return false;
        }

        $transaction->loadMissing(['transactionItems.product', 'transactionItems.variant', 'diningTable']);

        try {
            Mail::to($email)->queue(new ReceiptMail($transaction));
        } catch (\Throwable) {
            Transaction::query()
                ->whereKey($transaction->id)
                ->update(['receipt_emailed_at' => null]);

            return false;
        }

        return true;
    }
}
