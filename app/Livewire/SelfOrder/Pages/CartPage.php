<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Components\CartBadge;
use App\Livewire\SelfOrder\Traits\CartManagement;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Component;

class CartPage extends Component
{
    use CartManagement;

    public $foods;

    public $title = 'All Foods';

    #[Session(key: 'cart_items')]
    public $cartItems = [];

    #[Session(key: 'has_unpaid_transaction')]
    public $hasUnpaidTransaction;

    public function mount()
    {
        $this->cartItems = session('cart_items', []);
        $this->updateTotals();
    }

    public function clearCart()
    {
        $this->cartItems = [];
        session(['cart_items' => []]);
        session(['has_unpaid_transaction' => false]);
        $this->dispatch('cart-updated')->to(CartBadge::class);
    }

    public function checkout()
    {
        if (empty($this->cartItems)) {
            $this->addError('cartItems', 'Keranjang kosong. Tambahkan item terlebih dahulu.');

            return;
        }

        session(['cart_items' => $this->cartItems]);

        return $this->redirectRoute('self-order.payment.page', navigate: true);
    }

    public function saveNote(int $index)
    {
        if (! isset($this->cartItems[$index])) {
            return;
        }

        session(['cart_items' => $this->cartItems]);
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.payment.cart');
    }
}
