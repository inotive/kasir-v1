<?php

namespace App\Livewire\SelfOrder\Traits;

use App\Models\Member;

trait RequiresMemberSession
{
    protected function memberFromSession(): ?Member
    {
        if ((string) session('customer_type') !== 'member') {
            return null;
        }

        $memberId = session('member_id');
        if (! is_numeric($memberId)) {
            return null;
        }

        return Member::query()->find((int) $memberId);
    }

    protected function redirectIfNotMember(): ?Member
    {
        $member = $this->memberFromSession();
        if (! $member) {
            $this->redirectRoute('self-order.start', navigate: true);

            return null;
        }

        return $member;
    }
}
