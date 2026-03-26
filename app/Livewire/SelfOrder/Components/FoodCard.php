<?php

namespace App\Livewire\SelfOrder\Components;

use Illuminate\Contracts\View\View;
use Livewire\Component;

class FoodCard extends Component
{
    public $data;

    public bool $isGrid = true;

    public function openVariants(): void
    {
        $id = (int) ($this->data->id ?? 0);
        if ($id <= 0) {
            return;
        }

        $this->dispatch('open-product-variants', productId: $id);
    }

    public function render(): View
    {
        return view('livewire.self-order.components.food-card');
    }
}
