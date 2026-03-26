<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Traits\RequiresMemberSession;
use App\Models\Transaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

class MemberTransactionsPage extends Component
{
    use RequiresMemberSession;
    use WithPagination;

    public int $perPage = 10;

    public int $memberId = 0;

    public function mount(): void
    {
        $member = $this->redirectIfNotMember();
        if (! $member) {
            return;
        }

        $this->memberId = (int) $member->id;
    }

    public function getTransactionsProperty(): LengthAwarePaginator
    {
        if ($this->memberId <= 0) {
            return Transaction::query()->whereRaw('1=0')->paginate($this->perPage);
        }

        return Transaction::query()
            ->where('member_id', (int) $this->memberId)
            ->latest()
            ->paginate($this->perPage);
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.members.transactions');
    }
}
