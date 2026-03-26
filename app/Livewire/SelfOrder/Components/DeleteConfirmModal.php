<?php

namespace App\Livewire\SelfOrder\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class DeleteConfirmModal extends Component
{
    public function render(): View
    {
        return view('livewire.self-order.components.delete-confirm-modal');
    }
}
