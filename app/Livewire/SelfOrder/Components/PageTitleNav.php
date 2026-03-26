<?php

namespace App\Livewire\SelfOrder\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class PageTitleNav extends Component
{
    public string $title;

    public bool $showModal = false;

    public bool $hasBack = false;

    public bool $hasFilter = true;

    public bool $backCart = false;

    public bool $backTransactions = false;

    protected $listeners = ['showModal' => 'openModal'];

    public function openModal(): void
    {
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    public function render(): View
    {
        return view('livewire.self-order.components.page-title-nav');
    }
}
