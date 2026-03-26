<?php

namespace App\Mail;

use App\Models\Setting;
use App\Models\Transaction;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class ReceiptMail extends Mailable
{
    use Queueable, SerializesModels;

    public Transaction $transaction;

    public function __construct(Transaction $transaction)
    {
        $this->transaction = $transaction;
    }

    public function build()
    {
        $storeName = (string) (Setting::current()->store_name ?? config('app.name'));
        $fromAddress = (string) config('mail.from.address');

        return $this->subject('Struk Pembayaran #'.(string) $this->transaction->code)
            ->when($fromAddress !== '', fn (self $m) => $m->from($fromAddress, $storeName))
            ->view('livewire.emails.receipt')
            ->with([
                'transaction' => $this->transaction,
            ]);
    }
}
