<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Traits\RequiresMemberSession;
use Livewire\Attributes\Layout;
use Livewire\Component;

class MemberAccountPage extends Component
{
    use RequiresMemberSession;

    public int $points = 0;

    public function mount(): void
    {
        $member = $this->redirectIfNotMember();
        if (! $member) {
            return;
        }

        $this->points = (int) ($member->points ?? 0);
    }

    public function logoutMember(): void
    {
        session()->forget([
            'customer_ready',
            'customer_type',
            'member_id',
            'name',
            'phone',
            'email',
        ]);

        $this->redirectRoute('self-order.start', navigate: true);
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.members.account');
    }
}
