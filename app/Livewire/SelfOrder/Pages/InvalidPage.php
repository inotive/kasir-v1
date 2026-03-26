<?php

namespace App\Livewire\SelfOrder\Pages;

use Livewire\Attributes\Layout;
use Livewire\Component;

class InvalidPage extends Component
{
    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.invalid');
    }
}
