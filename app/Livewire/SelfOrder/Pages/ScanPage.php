<?php

namespace App\Livewire\SelfOrder\Pages;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

class ScanPage extends Component
{
    #[Layout('layouts.self-order')]
    public function render(): View
    {
        return view('livewire.self-order.scan', [
            'title' => 'Scan QR',
        ]);
    }
}
