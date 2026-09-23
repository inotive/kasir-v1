<?php

namespace App\Services\Whatsapp;

use App\Jobs\SendWaReceiptJob;
use App\Models\Tenant;
use App\Models\Transaction;
use App\Models\WhatsappSetting;
use App\Support\Phone\PhoneNumber;
use Illuminate\Support\Str;

class WhatsappReceiptService
{
    /**
     * POS checkout path: only fires when the cashier actually checked the "kirim WA" box.
     */
    public function queueIfRequested(Transaction $transaction, bool $requestedByCashier): bool
    {
        if (! $requestedByCashier) {
            return false;
        }

        return $this->attemptQueue($transaction, force: false);
    }

    /**
     * Self-order / online payment webhook path: no checkbox exists there, gated purely on the
     * tenant's WhatsApp toggle (mirrors ReceiptEmailService::queueIfNeeded()'s email-presence gate).
     */
    public function queueIfEnabled(Transaction $transaction): bool
    {
        $waSetting = WhatsappSetting::current();
        if (! $waSetting->is_enabled) {
            return false;
        }

        return $this->attemptQueue($transaction, force: false);
    }

    /**
     * Manual "Kirim Ulang via WA" action: bypasses the already-sent guard on purpose.
     */
    public function resend(Transaction $transaction, ?int $actorUserId = null): bool
    {
        return $this->attemptQueue($transaction, force: true);
    }

    private function attemptQueue(Transaction $transaction, bool $force): bool
    {
        $waSetting = WhatsappSetting::current();
        if (! $waSetting->is_enabled || ! $waSetting->openwa_session_id) {
            return false;
        }

        $transaction->loadMissing('member');
        $chatId = PhoneNumber::toWhatsAppChatId($transaction->member?->phone ?? $transaction->phone);
        if (! $chatId) {
            return false;
        }

        if ($transaction->wa_receipt_token === null) {
            Transaction::query()
                ->whereKey($transaction->id)
                ->whereNull('wa_receipt_token')
                ->update(['wa_receipt_token' => Str::random(48)]);
        }

        if ($force) {
            Transaction::query()->whereKey($transaction->id)->update(['wa_receipt_status' => 'queued']);
        } else {
            $updated = Transaction::query()
                ->whereKey($transaction->id)
                ->whereNull('wa_receipt_status')
                ->update(['wa_receipt_status' => 'queued']);

            if ($updated !== 1) {
                return false;
            }
        }

        $tenantId = Tenant::checkCurrent() ? (int) Tenant::current()->id : null;

        SendWaReceiptJob::dispatch((int) $transaction->id, $tenantId, $force);

        return true;
    }
}
