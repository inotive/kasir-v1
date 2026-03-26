<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Traits\RequiresMemberSession;
use App\Models\Transaction;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MemberTransactionShowPage extends Component
{
    use RequiresMemberSession;

    public ?Transaction $transaction = null;

    protected ?int $memberId = null;

    public function mount(Transaction $transaction): void
    {
        $member = $this->redirectIfNotMember();
        if (! $member) {
            return;
        }

        $this->memberId = (int) $member->id;

        if ((int) $transaction->member_id !== (int) $this->memberId) {
            $this->redirectRoute('self-order.member.transactions', navigate: true);

            return;
        }

        $this->transaction = Transaction::query()
            ->with([
                'diningTable:id,table_number',
                'transactionItems' => function ($q) {
                    $q->with([
                        'product:id,name',
                        'variant:id,name',
                        'childTransactionItems.product:id,name',
                        'childTransactionItems.variant:id,name',
                    ])->orderBy('id');
                },
            ])
            ->whereKey($transaction->id)
            ->first();
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.members.transaction-show');
    }
}
