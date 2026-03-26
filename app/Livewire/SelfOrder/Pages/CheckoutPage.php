<?php

namespace App\Livewire\SelfOrder\Pages;

use App\Livewire\SelfOrder\Traits\CartManagement;
use App\Models\DiningTable;
use App\Models\Product;
use App\Services\Inventory\VariantIngredientStockStatusService;
use Illuminate\Support\Str;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Component;

class CheckoutPage extends Component
{
    use CartManagement;

    public $name;

    public $phone;

    public $email;

    public $tableNumber;

    #[Session(key: 'has_unpaid_transaction')]
    public $hasUnpaidTransaction;

    #[Session(key: 'cart_items')]
    public $cartItems = [];

    #[Session(key: 'self_order_voucher_code')]
    public ?string $voucherCodeInput = null;

    public $title = 'All Foods';

    public $total;

    public $subtotal;

    public $tax;

    public $taxRate;

    public $rounding_adjustment;

    public $payment_gateway_enabled = true;

    public $paymentToken;

    public array $inventoryWarnings = [];

    public array $packageContentsByProductId = [];

    public function mount()
    {
        $this->name = session('name');
        $this->phone = session('phone');
        $this->email = session('email');
        $tableId = session('dining_table_id');
        $this->tableNumber = $tableId ? optional(DiningTable::find($tableId))->table_number : null;
        if (empty($this->cartItems)) {
            redirect()->route('self-order.payment.cart');

            return;
        }

        $this->paymentToken = Str::random(32);
        session(['payment_token' => $this->paymentToken]);

        $s = \App\Models\Setting::current();
        $this->payment_gateway_enabled = (bool) ($s->payment_gateway_enabled ?? true);

        $this->voucherCodeInput = session('self_order_voucher_code');
        $this->usePoints = (bool) session('self_order_use_points', false);
        $this->updateTotals();
        $this->computePackageContents();
        $this->computeInventoryWarnings();
    }

    #[Layout('layouts.self-order')]
    public function render()
    {
        return view('livewire.self-order.payment.checkout');
    }

    public function updatedName($value)
    {
        session(['name' => $value]);
    }

    public function updatedPhone($value)
    {
        session(['phone' => $value]);
    }

    public function updatedEmail($value)
    {
        session(['email' => $value]);
    }

    public function updatedVoucherCodeInput($value): void
    {
        $code = is_string($value) ? trim($value) : '';
        session(['self_order_voucher_code' => $code !== '' ? $code : null]);
        $this->updateTotals();
    }

    public function updatedUsePoints($value): void
    {
        $this->usePoints = (bool) $value;
        $this->updateTotals();

        if ($this->usePoints && ! $this->canUsePoints) {
            $this->usePoints = false;
            session(['self_order_use_points' => false]);
            $this->updateTotals();

            return;
        }

        session(['self_order_use_points' => (bool) $this->usePoints]);
    }

    private function computeInventoryWarnings(): void
    {
        $warnings = [];
        $variantIds = [];
        $namesByVariantId = [];

        $productIds = collect((array) $this->cartItems)
            ->map(fn (array $row) => (int) ($row['id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $packagesByProductId = $productIds === []
            ? collect()
            : Product::query()
                ->whereIn('id', $productIds)
                ->where('is_package', true)
                ->with(['packageItems.componentVariant.product'])
                ->get()
                ->keyBy('id');

        foreach ((array) $this->cartItems as $item) {
            $productId = (int) ($item['id'] ?? 0);
            $package = $packagesByProductId->get($productId);

            if ($package) {
                foreach ($package->packageItems as $packageItem) {
                    $componentVariant = $packageItem->componentVariant;
                    if (! $componentVariant) {
                        continue;
                    }

                    $variantId = (int) $componentVariant->id;
                    $variantIds[] = $variantId;

                    $productName = (string) ($componentVariant->product?->name ?? '');
                    $variantName = (string) ($componentVariant->name ?? '');
                    $namesByVariantId[$variantId] = trim('Isi '.$item['name'].' - '.$productName.($variantName !== '' ? ' ('.$variantName.')' : ''));
                }

                continue;
            }

            $variantId = (int) ($item['variant_id'] ?? 0);
            if ($variantId <= 0) {
                continue;
            }

            $variantIds[] = $variantId;
            $namesByVariantId[$variantId] = (string) ($item['name'] ?? 'Item');
        }

        $variantIds = array_values(array_unique($variantIds));
        $statuses = $variantIds === []
            ? []
            : app(VariantIngredientStockStatusService::class)->statusesForVariantIds($variantIds);

        foreach ($variantIds as $variantId) {
            $status = (string) ($statuses[$variantId] ?? '');
            $name = (string) ($namesByVariantId[$variantId] ?? 'Item');

            if ($status === 'missing_bom') {
                $warnings[] = 'Resep/BOM belum diatur untuk '.$name.'.';
            } elseif ($status === 'insufficient') {
                $warnings[] = 'Stok bahan kurang untuk '.$name.'.';
            } elseif ($status === 'low') {
                $warnings[] = 'Stok bahan menipis untuk '.$name.'.';
            }
        }

        $this->inventoryWarnings = array_values(array_unique(array_filter($warnings)));
    }

    private function computePackageContents(): void
    {
        $productIds = collect((array) $this->cartItems)
            ->map(fn (array $row) => (int) ($row['id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $packages = $productIds === []
            ? collect()
            : Product::query()
                ->whereIn('id', $productIds)
                ->where('is_package', true)
                ->with(['packageItems.componentVariant.product'])
                ->get();

        $this->packageContentsByProductId = $packages
            ->mapWithKeys(function (Product $p): array {
                $contents = $p->packageItems
                    ->map(function ($pi): string {
                        $qty = (int) ($pi->quantity ?? 0);
                        $productName = (string) ($pi->componentVariant?->product?->name ?? '');
                        $variantName = (string) ($pi->componentVariant?->name ?? '');
                        $label = trim($productName.($variantName !== '' ? ' ('.$variantName.')' : ''));

                        return ($qty > 0 ? $qty.'x ' : '').($label !== '' ? $label : 'Item');
                    })
                    ->filter(fn (string $s) => trim($s) !== '')
                    ->values()
                    ->all();

                return [(int) $p->id => $contents];
            })
            ->all();
    }
}
