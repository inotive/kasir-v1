<?php

namespace App\Livewire\SelfOrder\Components;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;

class CartBadge extends Component
{
    public bool $hidden = false;

    #[Session(key: 'cart_items')]
    public $cartItems = [];

    public ?int $lastCount = null;

    #[On('cart-updated')]
    #[On('check-cart-updates')]
    public function handleCartUpdated(): void
    {
        $this->cartItems = session('cart_items', []);
    }

    public function render(): View
    {
        $count = array_sum(array_map(function ($item) {
            return (int) ($item['quantity'] ?? 1);
        }, $this->cartItems ?? []));

        $subtotal = array_sum(array_map(function ($item) {
            $hasDiscount = isset($item['price_afterdiscount']) && (int) $item['price_afterdiscount'] > 0 && (int) $item['price_afterdiscount'] < (int) $item['price'];
            $price = $hasDiscount ? (int) $item['price_afterdiscount'] : (int) $item['price'];

            return $price * (int) ($item['quantity'] ?? 1);
        }, $this->cartItems ?? []));

        $totalPrice = $subtotal;

        $this->lastCount = $count;

        return view('livewire.self-order.components.cart-badge', [
            'count' => $count,
            'totalPrice' => $totalPrice,
        ]);
    }
}
