<?php

namespace App\Livewire\Pos;

use App\Events\SelfOrderPaymentUpdated;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\Transaction;
use App\Models\TransactionEvent;
use App\Models\TransactionItem;
use App\Models\VoucherCode;
use App\Models\VoucherRedemption;
use App\Services\Inventory\VariantIngredientStockStatusService;
use App\Services\Printing\PosPrintPayloadService;
use App\Support\Products\ItemNameFormatter;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Livewire\Component;

class PosPage extends Component
{
    public string $title = 'POS';

    public string $activeTab = 'pos';

    public string $search = '';

    public string $scanCode = '';

    public ?int $selectedCategoryId = null;

    public string $orderType = 'take_away';

    public ?int $selectedTableId = null;

    public bool $tableModalOpen = false;

    public bool $variantModalOpen = false;

    public ?int $variantProductId = null;

    public array $variantOptions = [];

    public bool $complexPackageModalOpen = false;

    public ?int $complexPackageParentVariantId = null;

    public array $complexPackageComponents = [];

    public ?int $editingComplexPackageCartIndex = null;

    public array $cartItems = [];

    public int $subtotal = 0;

    public int $netSubtotal = 0;

    public int $voucherDiscountAmount = 0;

    public ?string $voucherCodeInput = null;

    public string $voucherMessage = '';

    public bool $voucherValid = false;

    public array $voucherAllocations = [];

    public bool $cartLocked = false;

    public ?string $lockedVoucherCode = null;

    public int $lockedVoucherDiscountAmount = 0;

    public array $lockedVoucherAllocations = [];

    public int $lockedPointsToRedeem = 0;

    public int $lockedPointDiscountAmount = 0;

    public ?int $lockedMemberId = null;

    public ?string $lockedCustomerName = null;

    public ?string $lockedCustomerPhone = null;

    public ?string $manualDiscountType = null;

    public ?int $manualDiscountValue = null;

    public int $manualDiscountAmount = 0;

    public ?string $manualDiscountNote = null;

    public int $discountTotalAmount = 0;

    public int $memberPoints = 0;

    public bool $redeemPoints = false;

    public int $pointDiscountAmount = 0;

    public int $pointsToRedeem = 0;

    public int $minRedemptionPoints = 0;

    public float $pointRedemptionValue = 0;

    public float $taxRate = 0;

    public int $taxAmount = 0;

    public int $roundingAmount = 0;

    public int $total = 0;

    public int $roundingBase = 100;

    public bool $discountAppliesBeforeTax = true;

    public bool $checkoutModalOpen = false;

    public int $checkoutStep = 1;

    public bool $savePendingModalOpen = false;

    public bool $pendingOrdersModalOpen = false;

    public ?int $editingTransactionId = null;

    public ?int $memberId = null;

    public string $customerName = '';

    public ?string $customerPhone = null;

    public string $paymentMethod = 'cash';

    public ?string $cashReceived = null;

    public int $cashChange = 0;

    public array $productCards = [];

    public array $variantStockStatuses = [];

    public function mount(): void
    {
        $this->authorize('pos.access');

        $setting = Setting::current();
        $this->taxRate = (float) $setting->tax_rate;
        $this->roundingBase = max(0, (int) $setting->rounding_base);
        $this->discountAppliesBeforeTax = (bool) ($setting->discount_applies_before_tax ?? true);
        $this->minRedemptionPoints = (int) ($setting->min_redemption_points ?? 0);
        $this->pointRedemptionValue = (float) ($setting->point_redemption_value ?? 0);

        if ($this->customerName === '') {
            $this->customerName = (string) ($setting->pos_default_customer_name ?? 'Walk-in');
        }

        if ($this->paymentMethod === 'cash' && (string) ($setting->pos_default_payment_method ?? 'cash') !== 'cash') {
            $this->paymentMethod = (string) ($setting->pos_default_payment_method ?? 'cash');
        }

        $orderType = request()->query('type');
        if (in_array($orderType, ['take_away', 'dine_in'], true)) {
            $this->orderType = $orderType;
        }

        $tableId = request()->query('table');
        if ($tableId !== null && $tableId !== '') {
            $table = DiningTable::query()->find((int) $tableId);
            if ($table) {
                $this->orderType = 'dine_in';
                $this->selectedTableId = (int) $table->id;
            }
        }

        $tab = (string) request()->query('tab', '');
        if (in_array($tab, ['pos', 'self_order'], true)) {
            $this->activeTab = $tab;
        }

        if ($this->activeTab === 'pos') {
            $this->refreshProductCards();
        }

        $this->recalculateTotals();
    }

    public function updatedSearch(): void
    {
        if ($this->activeTab !== 'pos') {
            return;
        }

        $this->refreshProductCards();
        $this->loadVariantStockStatuses();
    }

    public function updatedSelectedCategoryId(): void
    {
        if ($this->activeTab !== 'pos') {
            return;
        }

        $this->refreshProductCards();
        $this->loadVariantStockStatuses();
    }

    public function updatedVoucherCodeInput(): void
    {
        $this->recalculateTotals();
    }

    public function updatedMemberId(): void
    {
        if ($this->cartLocked) {
            $this->memberId = $this->lockedMemberId;
            $this->dispatch('toast', type: 'error', message: 'Pesanan self-order tidak dapat mengubah member.');

            return;
        }

        if ($this->memberId && ! auth()->user()?->can('members.view')) {
            $this->memberId = null;
            $this->dispatch('toast', type: 'error', message: 'Anda tidak memiliki akses untuk memilih member.');

            return;
        }

        $this->memberPoints = 0;
        $this->redeemPoints = false;
        $this->pointsToRedeem = 0;

        if ($this->memberId) {
            $member = Member::query()->find($this->memberId);
            if ($member) {
                $this->customerName = (string) $member->name;
                $this->customerPhone = (string) $member->phone;
                $this->memberPoints = (int) $member->points;
            }
        }

        $this->recalculateTotals();
    }

    public function updatedCustomerName(): void
    {
        if ($this->cartLocked) {
            $this->customerName = (string) ($this->lockedCustomerName ?? $this->customerName);
            $this->dispatch('toast', type: 'error', message: 'Pesanan self-order tidak dapat mengubah data pelanggan.');
        }
    }

    public function updatedCustomerPhone(): void
    {
        if ($this->cartLocked) {
            $this->customerPhone = $this->lockedCustomerPhone;
            $this->dispatch('toast', type: 'error', message: 'Pesanan self-order tidak dapat mengubah data pelanggan.');

            return;
        }

        $this->recalculateTotals();
    }

    public function updatedManualDiscountType(): void
    {
        $this->resetValidation(['manualDiscountType', 'manualDiscountValue']);

        $type = $this->manualDiscountType !== null ? trim((string) $this->manualDiscountType) : '';
        if ($type === '') {
            $this->manualDiscountType = null;
            $this->manualDiscountValue = null;
            $this->manualDiscountNote = null;
        }

        $this->recalculateTotals();
    }

    public function updatedManualDiscountValue(): void
    {
        $this->resetValidation(['manualDiscountType', 'manualDiscountValue']);

        $this->recalculateTotals();
    }

    public function updatedRedeemPoints(): void
    {
        if ($this->redeemPoints && $this->memberPoints < $this->minRedemptionPoints) {
            $this->redeemPoints = false;
            $this->dispatch('toast', type: 'error', message: 'Poin member belum mencapai minimal penukaran.');

            return;
        }

        $this->recalculateTotals();
    }

    public function setTab(string $tab): void
    {
        if (! in_array($tab, ['pos', 'self_order'], true)) {
            return;
        }

        $this->activeTab = $tab;

        if ($tab === 'pos') {
            $this->refreshProductCards();
            $this->loadVariantStockStatuses();
        }
    }

    public function loadVariantStockStatuses(): void
    {
        if ($this->activeTab !== 'pos') {
            return;
        }

        $variantIds = [];

        foreach ($this->productCards as $product) {
            $variants = (array) ($product['variants'] ?? []);
            foreach ($variants as $variant) {
                $variantId = (int) ($variant['id'] ?? 0);
                if ($variantId > 0) {
                    $variantIds[] = $variantId;
                }
            }

            $componentVariantIds = (array) ($product['package_component_variant_ids'] ?? []);
            foreach ($componentVariantIds as $componentVariantId) {
                $variantId = (int) $componentVariantId;
                if ($variantId > 0) {
                    $variantIds[] = $variantId;
                }
            }
        }

        $variantIds = array_values(array_unique($variantIds));

        $this->variantStockStatuses = app(VariantIngredientStockStatusService::class)
            ->statusesForVariantIds($variantIds);
    }

    private function refreshProductCards(): void
    {
        $term = trim($this->search);

        $products = Product::query()
            ->where('is_available', true)
            ->when($this->selectedCategoryId, fn (Builder $q) => $q->where('category_id', $this->selectedCategoryId))
            ->when($term !== '', function (Builder $q) use ($term): void {
                $like = '%'.$term.'%';
                $q->where(function (Builder $qq) use ($like): void {
                    $qq->where('name', 'like', $like)->orWhere('description', 'like', $like);
                });
            })
            ->with(['variants' => function ($q): void {
                $q->orderBy('id');
            }, 'packageItems' => function ($q): void {
                $q->orderBy('sort_order')->select(['id', 'package_product_id', 'component_product_variant_id']);
            }])
            ->orderBy('name')
            ->limit(120)
            ->get(['id', 'name', 'image', 'is_package', 'package_type']);

        $this->productCards = $products->map(function (Product $product): array {
            $packageType = (string) ($product->package_type ?? 'simple');
            $isPackage = (bool) $product->is_package;

            return [
                'id' => (int) $product->id,
                'name' => (string) $product->name,
                'image' => (string) ($product->image ?? ''),
                'is_package' => $isPackage,
                'package_type' => $packageType,
                'package_component_variant_ids' => $isPackage && $packageType !== 'complex'
                    ? $product->packageItems
                        ->pluck('component_product_variant_id')
                        ->filter(fn ($v) => (int) $v > 0)
                        ->unique()
                        ->values()
                        ->map(fn ($v) => (int) $v)
                        ->all()
                    : [],
                'variants' => $product->variants
                    ->map(fn (ProductVariant $v) => [
                        'id' => (int) $v->id,
                        'name' => (string) $v->name,
                        'price' => (float) $v->price,
                        'price_afterdiscount' => $v->price_afterdiscount === null ? null : (float) $v->price_afterdiscount,
                        'percent' => $v->percent === null ? null : (int) $v->percent,
                    ])
                    ->values()
                    ->all(),
            ];
        })->values()->all();
    }

    public function chooseOrderType(string $type): void
    {
        if (! in_array($type, ['take_away', 'dine_in'], true)) {
            return;
        }

        $this->orderType = $type;
        if ($type === 'dine_in') {
            $this->tableModalOpen = true;

            return;
        }

        $this->selectedTableId = null;
        $this->tableModalOpen = false;
    }

    public function selectTable(int $id): void
    {
        $table = DiningTable::query()->find($id);
        if (! $table) {
            return;
        }

        $this->selectedTableId = (int) $table->id;
        $this->tableModalOpen = false;
    }

    public function openVariantModal(int $productId): void
    {
        $product = Product::query()->whereKey($productId)->first();
        if (! $product) {
            return;
        }

        $variants = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('name')
            ->get(['id', 'name', 'price', 'price_afterdiscount', 'percent']);

        if ($variants->count() <= 1) {
            $variant = $variants->first();
            if ($variant) {
                $this->addVariantToCart((int) $variant->id);
            }

            return;
        }

        $this->variantOptions = $variants->map(function (ProductVariant $v) {
            $base = (int) round((float) $v->price);
            $final = $this->finalVariantPrice($v);

            return [
                'id' => (int) $v->id,
                'name' => (string) $v->name,
                'price' => $base,
                'final_price' => $final,
                'percent' => $v->percent === null ? null : (int) $v->percent,
            ];
        })->all();

        $this->variantProductId = (int) $productId;
        $this->variantModalOpen = true;
    }

    public function addToCart(int $productId): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        $product = Product::query()->whereKey($productId)->first();
        if (! $product) {
            return;
        }

        $variantsCount = ProductVariant::query()->where('product_id', $productId)->count();
        if ($variantsCount <= 0) {
            $this->dispatch('toast', type: 'error', message: 'Produk belum memiliki varian harga.');

            return;
        }

        if ($variantsCount > 1) {
            $this->openVariantModal($productId);

            return;
        }

        $variant = ProductVariant::query()
            ->where('product_id', $productId)
            ->orderBy('id')
            ->first();

        if ($variant) {
            $this->addVariantToCart((int) $variant->id);
        }
    }

    public function addVariantToCart(int $variantId): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        $variant = ProductVariant::query()
            ->with('product')
            ->find($variantId);

        if (! $variant || ! $variant->product) {
            return;
        }

        if ((bool) $variant->product->is_package && (string) ($variant->product->package_type ?? 'simple') === 'complex') {
            $this->openComplexPackageModal((int) $variant->id);
            $this->variantModalOpen = false;

            return;
        }

        $finalPrice = $this->finalVariantPrice($variant);
        $base = (int) round((float) $variant->price);

        $existingIndex = null;
        foreach ($this->cartItems as $i => $item) {
            if ((int) ($item['variant_id'] ?? 0) === (int) $variant->id) {
                $existingIndex = $i;
                break;
            }
        }

        if ($existingIndex !== null) {
            $this->cartItems[$existingIndex]['quantity'] = (int) ($this->cartItems[$existingIndex]['quantity'] ?? 0) + 1;
            $this->recalculateTotals();
            $this->variantModalOpen = false;

            return;
        }

        $this->cartItems[] = [
            'product_id' => (int) $variant->product->id,
            'variant_id' => (int) $variant->id,
            'name' => (string) $variant->product->name,
            'variant_name' => ItemNameFormatter::displayVariantName((int) $variant->product->id, (string) $variant->name),
            'price' => $finalPrice,
            'original_price' => $base,
            'percent' => $variant->percent === null ? null : (int) $variant->percent,
            'quantity' => 1,
            'note' => null,
        ];

        $this->recalculateTotals();
        $this->variantModalOpen = false;
    }

    public function openComplexPackageModal(int $parentVariantId): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        $parentVariant = ProductVariant::query()
            ->with(['product.complexPackageItems.componentProduct'])
            ->find($parentVariantId);

        if (! $parentVariant || ! $parentVariant->product) {
            return;
        }

        $this->populateComplexPackageModal($parentVariant, null);
    }

    public function editComplexPackageInCart(int $index): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        if (! isset($this->cartItems[$index]) || ! is_array($this->cartItems[$index])) {
            return;
        }

        $cartItem = $this->cartItems[$index];
        if ((string) ($cartItem['package_type'] ?? '') !== 'complex') {
            return;
        }

        $parentVariantId = (int) ($cartItem['variant_id'] ?? 0);
        if ($parentVariantId <= 0) {
            return;
        }

        $parentVariant = ProductVariant::query()
            ->with(['product.complexPackageItems.componentProduct'])
            ->find($parentVariantId);

        if (! $parentVariant || ! $parentVariant->product) {
            return;
        }

        $this->editingComplexPackageCartIndex = $index;
        $this->populateComplexPackageModal($parentVariant, $cartItem);
    }

    private function populateComplexPackageModal(ProductVariant $parentVariant, ?array $cartItem): void
    {
        if (! $parentVariant->product) {
            return;
        }

        $product = $parentVariant->product;
        if (! (bool) $product->is_package || (string) ($product->package_type ?? 'simple') !== 'complex') {
            return;
        }

        $componentProductIds = $product->complexPackageItems
            ->pluck('component_product_id')
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->values()
            ->all();

        if ($componentProductIds === []) {
            $this->dispatch('toast', type: 'error', message: 'Isi paket belum diatur.');

            return;
        }

        $variantRows = ProductVariant::query()
            ->whereIn('product_id', $componentProductIds)
            ->orderBy('name')
            ->get(['id', 'product_id', 'name'])
            ->groupBy('product_id');

        $this->complexPackageParentVariantId = (int) $parentVariant->id;
        $existingByProductId = collect((array) ($cartItem['package_components'] ?? []))
            ->filter(fn ($row) => is_array($row))
            ->groupBy(fn (array $row) => (int) ($row['product_id'] ?? 0));

        $this->complexPackageComponents = $product->complexPackageItems
            ->values()
            ->map(function ($item) use ($variantRows, $existingByProductId) {
                $componentProductId = (int) $item->component_product_id;
                $baseQty = (int) $item->quantity;
                $isSplitable = (bool) ($item->is_splitable ?? false);
                $options = $variantRows->get($componentProductId, collect())
                    ->map(fn (ProductVariant $v) => [
                        'id' => (int) $v->id,
                        'name' => (string) $v->name,
                    ])
                    ->values()
                    ->all();

                $existing = $existingByProductId->get($componentProductId, collect())
                    ->map(fn ($row) => is_array($row) ? $row : [])
                    ->filter(fn (array $row) => (int) ($row['quantity'] ?? 0) > 0)
                    ->values();

                $allocations = $existing->map(fn (array $row) => [
                    'key' => (string) Str::uuid(),
                    'quantity' => (int) ($row['quantity'] ?? 0),
                    'variant_id' => (int) ($row['variant_id'] ?? 0),
                    'note' => array_key_exists('note', $row) ? ($row['note'] === '' ? null : (string) $row['note']) : null,
                ])->all();

                if (! $isSplitable) {
                    $merged = [];
                    foreach ($allocations as $row) {
                        $variantId = (int) ($row['variant_id'] ?? 0);
                        $note = $row['note'] ?? null;
                        $key = $variantId.'|'.trim((string) ($note ?? ''));
                        if (! array_key_exists($key, $merged)) {
                            $merged[$key] = $row;

                            continue;
                        }
                        $merged[$key]['quantity'] += (int) ($row['quantity'] ?? 0);
                    }
                    $allocations = array_values($merged);
                    $allocations = $allocations === [] ? [] : [$allocations[0]];
                    if ($allocations !== []) {
                        $allocations[0]['quantity'] = $baseQty;
                    }
                }

                if ($allocations === []) {
                    $allocations = [[
                        'key' => (string) Str::uuid(),
                        'quantity' => $baseQty,
                        'variant_id' => null,
                        'note' => null,
                    ]];
                } else {
                    $sum = collect($allocations)->sum(fn ($a) => (int) ($a['quantity'] ?? 0));
                    if ($sum < $baseQty) {
                        $allocations[] = [
                            'key' => (string) Str::uuid(),
                            'quantity' => $baseQty - (int) $sum,
                            'variant_id' => null,
                            'note' => null,
                        ];
                    }
                    if ($sum > $baseQty) {
                        $left = $baseQty;
                        $normalized = [];
                        foreach ($allocations as $row) {
                            $qty = (int) ($row['quantity'] ?? 0);
                            if ($left <= 0) {
                                break;
                            }
                            $take = min($left, $qty);
                            $row['quantity'] = $take;
                            $normalized[] = $row;
                            $left -= $take;
                        }
                        $allocations = $normalized === [] ? [[
                            'key' => (string) Str::uuid(),
                            'quantity' => $baseQty,
                            'variant_id' => null,
                            'note' => null,
                        ]] : $normalized;
                    }
                }

                return [
                    'key' => (string) Str::uuid(),
                    'component_product_id' => $componentProductId,
                    'component_product_name' => (string) ($item->componentProduct?->name ?? ''),
                    'base_quantity' => $baseQty,
                    'is_splitable' => $isSplitable,
                    'variant_options' => $options,
                    'allocations' => $allocations,
                ];
            })
            ->all();

        if ($cartItem === null) {
            $this->editingComplexPackageCartIndex = null;
        }

        $this->complexPackageModalOpen = true;
    }

    public function addComplexPackageAllocation(string $componentKey): void
    {
        foreach ($this->complexPackageComponents as $index => $component) {
            if ((string) ($component['key'] ?? '') !== $componentKey) {
                continue;
            }

            if (! (bool) ($component['is_splitable'] ?? false)) {
                return;
            }

            $baseQty = (int) ($component['base_quantity'] ?? 0);
            if ($baseQty <= 0) {
                return;
            }

            $allocations = collect((array) ($component['allocations'] ?? []))
                ->filter(fn ($row) => is_array($row))
                ->values()
                ->all();

            $splitFromIndex = null;
            foreach ($allocations as $i => $row) {
                if ((int) ($row['quantity'] ?? 0) > 1) {
                    $splitFromIndex = $i;
                    break;
                }
            }

            if ($splitFromIndex === null) {
                return;
            }

            $allocations[$splitFromIndex]['quantity'] = (int) ($allocations[$splitFromIndex]['quantity'] ?? 0) - 1;

            $allocations[] = [
                'key' => (string) Str::uuid(),
                'quantity' => 1,
                'variant_id' => null,
                'note' => null,
            ];

            $this->complexPackageComponents[$index]['allocations'] = array_values($allocations);

            return;
        }
    }

    public function removeComplexPackageAllocation(string $componentKey, string $allocationKey): void
    {
        foreach ($this->complexPackageComponents as $index => $component) {
            if ((string) ($component['key'] ?? '') !== $componentKey) {
                continue;
            }

            if (! (bool) ($component['is_splitable'] ?? false)) {
                return;
            }

            $baseQty = (int) ($component['base_quantity'] ?? 0);
            $allocations = collect((array) ($component['allocations'] ?? []))
                ->filter(fn ($row) => is_array($row))
                ->reject(fn (array $row) => (string) ($row['key'] ?? '') === $allocationKey)
                ->values()
                ->all();

            if ($allocations === []) {
                $allocations = [[
                    'key' => (string) Str::uuid(),
                    'quantity' => $baseQty,
                    'variant_id' => null,
                    'note' => null,
                ]];
            } else {
                $sum = collect($allocations)->sum(fn ($row) => (int) ($row['quantity'] ?? 0));
                $diff = $baseQty - (int) $sum;
                if ($diff > 0) {
                    $allocations[0]['quantity'] = (int) ($allocations[0]['quantity'] ?? 0) + $diff;
                }
            }

            $this->complexPackageComponents[$index]['allocations'] = array_values($allocations);

            return;
        }
    }

    public function closeComplexPackageModal(): void
    {
        $this->complexPackageModalOpen = false;
        $this->complexPackageParentVariantId = null;
        $this->complexPackageComponents = [];
        $this->editingComplexPackageCartIndex = null;
    }

    public function confirmComplexPackageToCart(): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        $parentVariantId = $this->complexPackageParentVariantId;
        if ($parentVariantId === null) {
            return;
        }

        $parentVariant = ProductVariant::query()
            ->with('product')
            ->find($parentVariantId);

        if (! $parentVariant || ! $parentVariant->product) {
            return;
        }

        $components = $this->complexPackageComponents;
        $packageComponents = [];
        foreach ($components as $row) {
            if (! is_array($row)) {
                continue;
            }

            $componentProductId = (int) ($row['component_product_id'] ?? 0);
            $baseQty = (int) ($row['base_quantity'] ?? 0);
            $allocations = (array) ($row['allocations'] ?? []);

            if ($componentProductId <= 0 || $baseQty <= 0) {
                continue;
            }

            $allowed = collect($row['variant_options'] ?? [])
                ->map(fn ($v) => (int) ($v['id'] ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->values()
                ->all();

            $sumQty = 0;
            foreach ($allocations as $alloc) {
                if (! is_array($alloc)) {
                    continue;
                }

                $variantId = (int) ($alloc['variant_id'] ?? 0);
                $qty = (int) ($alloc['quantity'] ?? 0);
                $note = $alloc['note'] ?? null;

                if ($qty <= 0 || $variantId <= 0) {
                    $this->dispatch('toast', type: 'error', message: 'Semua komponen paket harus memilih varian dan qty valid.');

                    return;
                }

                if ($allowed !== [] && ! in_array($variantId, $allowed, true)) {
                    $this->dispatch('toast', type: 'error', message: 'Varian komponen tidak valid.');

                    return;
                }

                $sumQty += $qty;

                $packageComponents[] = [
                    'product_id' => $componentProductId,
                    'variant_id' => $variantId,
                    'quantity' => $qty,
                    'note' => $note === '' ? null : $note,
                ];
            }

            if ($sumQty !== $baseQty) {
                $this->dispatch('toast', type: 'error', message: 'Total qty komponen paket harus sesuai dengan qty paket.');

                return;
            }
        }

        $finalPrice = $this->finalVariantPrice($parentVariant);
        $base = (int) round((float) $parentVariant->price);

        $payload = [
            'product_id' => (int) $parentVariant->product->id,
            'variant_id' => (int) $parentVariant->id,
            'name' => (string) $parentVariant->product->name,
            'variant_name' => ItemNameFormatter::displayVariantName((int) $parentVariant->product->id, (string) $parentVariant->name),
            'price' => $finalPrice,
            'original_price' => $base,
            'percent' => $parentVariant->percent === null ? null : (int) $parentVariant->percent,
            'quantity' => 1,
            'note' => null,
            'package_type' => 'complex',
            'package_components' => collect($packageComponents)->values()->all(),
        ];

        if ($this->editingComplexPackageCartIndex !== null) {
            $index = $this->editingComplexPackageCartIndex;
            if (isset($this->cartItems[$index]) && is_array($this->cartItems[$index])) {
                $payload['quantity'] = (int) ($this->cartItems[$index]['quantity'] ?? 1);
                $payload['note'] = $this->cartItems[$index]['note'] ?? null;
                $this->cartItems[$index] = array_merge($this->cartItems[$index], $payload);
            }
        } else {
            $this->cartItems[] = $payload;
        }

        $this->recalculateTotals();
        $this->closeComplexPackageModal();
        $this->variantModalOpen = false;
    }

    public function increment(int $index): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        if (! isset($this->cartItems[$index])) {
            return;
        }

        $this->cartItems[$index]['quantity'] = (int) ($this->cartItems[$index]['quantity'] ?? 0) + 1;
        $this->recalculateTotals();
    }

    public function decrement(int $index): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        if (! isset($this->cartItems[$index])) {
            return;
        }

        $qty = (int) ($this->cartItems[$index]['quantity'] ?? 0);
        $qty--;

        if ($qty <= 0) {
            $this->removeItem($index);

            return;
        }

        $this->cartItems[$index]['quantity'] = $qty;
        $this->recalculateTotals();
    }

    public function removeItem(int $index): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan yang dimuat tidak dapat diubah.');

            return;
        }

        if (! isset($this->cartItems[$index])) {
            return;
        }

        array_splice($this->cartItems, $index, 1);
        $this->recalculateTotals();
    }

    public function clearCart(): void
    {
        $this->cartItems = [];
        $this->editingTransactionId = null;
        $this->cartLocked = false;
        $this->lockedVoucherCode = null;
        $this->lockedVoucherDiscountAmount = 0;
        $this->lockedVoucherAllocations = [];
        $this->lockedPointsToRedeem = 0;
        $this->lockedPointDiscountAmount = 0;
        $this->lockedMemberId = null;
        $this->lockedCustomerName = null;
        $this->lockedCustomerPhone = null;
        $this->recalculateTotals();
    }

    private function resetOrderForNewTransaction(): void
    {
        $setting = Setting::current();

        $this->clearCart();
        $this->orderType = 'take_away';
        $this->selectedTableId = null;
        $this->memberId = null;
        $this->customerName = (string) ($setting->pos_default_customer_name ?? 'Walk-in');
        $this->customerPhone = null;
    }

    public function openCheckout(): void
    {
        $this->resetValidation();
        $this->cashReceived = null;
        $this->cashChange = 0;
        $this->checkoutStep = 1;
        $this->checkoutModalOpen = true;
    }

    public function nextStep(): void
    {
        if ($this->checkoutStep === 1) {
            $this->validate([
                'customerName' => ['required', 'string', 'max:255'],
                'customerPhone' => ['nullable', 'string', 'max:50'],
            ]);

            $this->checkoutStep = 2;
        } elseif ($this->checkoutStep === 2) {
            if (! $this->ensureManualDiscountValid()) {
                return;
            }

            $this->checkoutStep = 3;
        }
    }

    public function prevStep(): void
    {
        if ($this->checkoutStep > 1) {
            $this->checkoutStep--;
        }
    }

    public function openSavePending(): void
    {
        if ($this->cartLocked) {
            $this->dispatch('toast', type: 'error', message: 'Pesanan self-order tidak bisa disimpan ulang.');

            return;
        }

        $this->resetValidation();
        $this->savePendingModalOpen = true;
    }

    public function openPendingOrders(): void
    {
        $this->authorize('transactions.view');

        $this->resetValidation();
        $this->pendingOrdersModalOpen = true;
    }

    public function loadPending(int $transactionId): void
    {
        $this->authorize('transactions.details');

        $trx = Transaction::query()
            ->with(['transactionItems.product', 'transactionItems.variant', 'diningTable'])
            ->whereKey($transactionId)
            ->where('payment_status', 'pending')
            ->whereIn('channel', ['pos', 'self_order'])
            ->first();

        if (! $trx) {
            return;
        }

        $this->editingTransactionId = (int) $trx->id;
        $this->orderType = (string) $trx->order_type;
        $this->selectedTableId = $trx->dining_table_id === null ? null : (int) $trx->dining_table_id;
        $this->memberId = $trx->member_id === null ? null : (int) $trx->member_id;
        $this->customerName = (string) $trx->name;
        $this->customerPhone = $trx->phone;
        $this->cartLocked = (string) $trx->channel === 'self_order';
        $this->lockedMemberId = $this->cartLocked ? $this->memberId : null;
        $this->lockedCustomerName = $this->cartLocked ? $this->customerName : null;
        $this->lockedCustomerPhone = $this->cartLocked ? $this->customerPhone : null;

        $displayItems = $trx->transactionItems
            ->whereNull('parent_transaction_item_id')
            ->values();

        $this->lockedVoucherCode = $trx->voucher_code === null ? null : (string) $trx->voucher_code;
        $this->lockedVoucherDiscountAmount = (int) ($trx->voucher_discount_amount ?? 0);
        $this->lockedVoucherAllocations = $displayItems->mapWithKeys(function (TransactionItem $item, int $index): array {
            return [$index => (int) ($item->voucher_discount_amount ?? 0)];
        })->all();
        $this->lockedPointsToRedeem = (int) ($trx->points_redeemed ?? 0);
        $this->lockedPointDiscountAmount = (int) ($trx->point_discount_amount ?? 0);

        $this->cartItems = $displayItems->map(function (TransactionItem $item) use ($trx) {
            $name = $item->product ? (string) $item->product->name : 'Produk';
            $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
            $price = (int) round((float) $item->price);

            $payload = [
                'product_id' => (int) $item->product_id,
                'variant_id' => $item->product_variant_id === null ? 0 : (int) $item->product_variant_id,
                'name' => $name,
                'variant_name' => $variantName,
                'price' => $price,
                'original_price' => $price,
                'percent' => null,
                'quantity' => (int) $item->quantity,
                'note' => $item->note,
            ];

            if ($item->product && (bool) $item->product->is_package && (string) ($item->product->package_type ?? 'simple') === 'complex') {
                $children = $trx->transactionItems
                    ->where('parent_transaction_item_id', (int) $item->id)
                    ->values();

                $parentQty = (int) $item->quantity;
                $payload['package_type'] = 'complex';
                $payload['package_components'] = $children->map(fn (TransactionItem $child) => [
                    'product_id' => (int) $child->product_id,
                    'variant_id' => $child->product_variant_id === null ? 0 : (int) $child->product_variant_id,
                    'quantity' => $parentQty > 0 ? (int) max(1, (int) round(((int) $child->quantity) / $parentQty)) : (int) $child->quantity,
                    'note' => $child->note,
                ])->all();
            }

            return $payload;
        })->all();

        $this->taxRate = $trx->tax_percentage === null ? $this->taxRate : (float) $trx->tax_percentage;
        $this->voucherCodeInput = $this->lockedVoucherCode;
        $this->recalculateTotals();
        $this->pendingOrdersModalOpen = false;
    }

    public function takeSelfOrderPending(int $transactionId): void
    {
        $this->activeTab = 'pos';
        $this->loadPending($transactionId);
    }

    public function markSelfOrderProcessed(int $transactionId): void
    {
        $this->authorize('transactions.print');

        $payload = null;
        $didUpdate = false;

        DB::transaction(function () use ($transactionId, &$didUpdate): void {
            $trx = Transaction::query()
                ->whereKey($transactionId)
                ->lockForUpdate()
                ->first();

            if (! $trx) {
                return;
            }

            if ((string) $trx->channel !== 'self_order') {
                return;
            }

            if ((string) $trx->payment_method !== 'qris_midtrans') {
                return;
            }

            if ((string) $trx->payment_status !== 'paid') {
                return;
            }

            if ((bool) $trx->is_midtrans_processed) {
                return;
            }

            $trx->forceFill(['is_midtrans_processed' => true])->save();
            $didUpdate = true;
        });

        if ($didUpdate) {
            $payload = app(PosPrintPayloadService::class)->build($transactionId);
        }

        if ($payload) {
            $this->dispatch('pos-print-modal', payload: $payload, context: 'midtrans');
        }

        if ($didUpdate) {
            $this->dispatch('midtrans-processed');
            $this->dispatch('toast', type: 'success', message: 'Transaksi ditandai sudah diproses.');

            return;
        }
        $this->dispatch('toast', type: 'error', message: 'Transaksi tidak dapat diproses.');
    }

    public function saveAsPending(): void
    {
        if (count($this->cartItems) === 0) {
            return;
        }

        if ($this->cartLocked && $this->editingTransactionId !== null) {
            $this->reloadCartItemsFromTransaction((int) $this->editingTransactionId);
        } else {
            $this->applyVariantPricesToCartItems();
        }

        $this->recalculateTotals();

        if (! $this->ensureManualDiscountValid()) {
            return;
        }

        $voucherCode = null;
        $voucherCampaignId = null;
        $voucherCodeId = null;
        if ($this->voucherValid && trim((string) $this->voucherCodeInput) !== '') {
            $voucherCode = strtoupper(trim((string) $this->voucherCodeInput));
            $row = VoucherCode::query()->where('code', $voucherCode)->where('is_active', true)->first();
            if ($row && $row->campaign) {
                $voucherCampaignId = (int) $row->voucher_campaign_id;
                $voucherCodeId = (int) $row->id;
            } else {
                $voucherCode = null;
            }
        }

        $trxId = null;
        $validated = $this->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
        ]);

        $manualTypeForPermission = $this->manualDiscountType !== null ? trim((string) $this->manualDiscountType) : '';
        $manualValueForPermission = $this->manualDiscountValue === null ? 0 : (int) $this->manualDiscountValue;

        if (($manualValueForPermission > 0 || $manualTypeForPermission !== '') && ! $this->userHasManualDiscountPermission()) {
            $this->addError('manualDiscountType', 'Anda tidak memiliki izin untuk memberikan diskon manual.');

            return;
        }

        if ($this->orderType === 'dine_in' && ! $this->selectedTableId) {
            $this->dispatch('toast', type: 'error', message: 'Order dine-in wajib memilih meja.');

            return;
        }

        DB::transaction(function () use ($validated, $voucherCampaignId, $voucherCodeId, $voucherCode, &$trxId) {
            $trx = $this->editingTransactionId
                ? Transaction::query()->whereKey($this->editingTransactionId)->lockForUpdate()->first()
                : null;

            $manualType = $this->manualDiscountAmount > 0 ? $this->manualDiscountType : null;
            $manualValue = $this->manualDiscountAmount > 0 ? $this->manualDiscountValue : null;
            $manualNote = $this->manualDiscountAmount > 0 ? $this->manualDiscountNote : null;

            if (! $trx) {
                $trx = Transaction::query()->create([
                    'code' => Transaction::generateUniqueCode(),
                    'member_id' => $this->memberId,
                    'channel' => 'pos',
                    'name' => $validated['customerName'],
                    'phone' => $validated['customerPhone'] !== '' ? $validated['customerPhone'] : null,
                    'email' => null,
                    'order_type' => $this->orderType,
                    'dining_table_id' => $this->orderType === 'dine_in' ? $this->selectedTableId : null,
                    'voucher_campaign_id' => $voucherCampaignId,
                    'voucher_code_id' => $voucherCodeId,
                    'voucher_code' => $voucherCode,
                    'subtotal' => $this->subtotal,
                    'voucher_discount_amount' => $this->voucherDiscountAmount,
                    'manual_discount_type' => $manualType,
                    'manual_discount_value' => $manualValue,
                    'manual_discount_amount' => $this->manualDiscountAmount,
                    'manual_discount_note' => $manualNote,
                    'manual_discount_by_user_id' => auth()->id(),
                    'discount_total_amount' => $this->discountTotalAmount,
                    'point_discount_amount' => 0, // Will be set by redeemPoints
                    'points_redeemed' => 0, // Will be set by redeemPoints
                    'points_earned' => 0, // Will be calculated by observer
                    'tax_percentage' => $this->taxRate,
                    'tax_amount' => $this->taxAmount,
                    'rounding_amount' => $this->roundingAmount,
                    'cash_received' => null,
                    'cash_change' => null,
                    'total' => $this->total,
                    'checkout_link' => '',
                    'payment_method' => 'pending',
                    'payment_status' => 'pending',
                    'order_status' => 'new',
                    'external_id' => Transaction::generateUniqueCode(10),
                ]);
            } else {
                $trx->update([
                    'member_id' => $this->memberId,
                    'name' => $validated['customerName'],
                    'phone' => $validated['customerPhone'] !== '' ? $validated['customerPhone'] : null,
                    'order_type' => $this->orderType,
                    'dining_table_id' => $this->orderType === 'dine_in' ? $this->selectedTableId : null,
                    'voucher_campaign_id' => $voucherCampaignId,
                    'voucher_code_id' => $voucherCodeId,
                    'voucher_code' => $voucherCode,
                    'subtotal' => $this->subtotal,
                    'voucher_discount_amount' => $this->voucherDiscountAmount,
                    'manual_discount_type' => $manualType,
                    'manual_discount_value' => $manualValue,
                    'manual_discount_amount' => $this->manualDiscountAmount,
                    'manual_discount_note' => $manualNote,
                    'manual_discount_by_user_id' => auth()->id(),
                    'discount_total_amount' => $this->discountTotalAmount,
                    'point_discount_amount' => 0,
                    'points_redeemed' => 0,
                    'points_earned' => 0,
                    'tax_percentage' => $this->taxRate,
                    'tax_amount' => $this->taxAmount,
                    'rounding_amount' => $this->roundingAmount,
                    'total' => $this->total,
                    'payment_method' => 'pending',
                    'payment_status' => 'pending',
                    'order_status' => 'new',
                ]);
                TransactionItem::query()->where('transaction_id', $trx->id)->delete();
            }

            $manualAllocations = $this->allocateManualDiscount($this->cartItems, $this->manualDiscountAmount, $this->voucherAllocations);

            $productIds = collect($this->cartItems)
                ->map(fn (array $row) => (int) ($row['product_id'] ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $productsById = $productIds === []
                ? collect()
                : Product::query()
                    ->with(['packageItems.componentVariant.product'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

            foreach ($this->cartItems as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = (int) ($item['variant_id'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 0);
                $price = (int) ($item['price'] ?? 0);
                $note = $item['note'] ?? null;

                if ($productId <= 0 || $variantId <= 0 || $qty <= 0) {
                    continue;
                }

                $parent = TransactionItem::query()->create([
                    'transaction_id' => $trx->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $qty * $price,
                    'voucher_discount_amount' => (int) ($this->voucherAllocations[$index] ?? 0),
                    'manual_discount_amount' => (int) ($manualAllocations[$index] ?? 0),
                    'note' => $note === '' ? null : $note,
                ]);

                $product = $productsById->get($productId);

                if (! $product || ! $product->is_package) {
                    continue;
                }

                $this->createPackageChildItems($trx, $parent, $product, $item, $qty);
            }
            $trxId = (int) $trx->id;
        });

        $this->dispatch('toast', type: 'success', message: 'Pesanan berhasil disimpan sebagai pending.');
        $this->savePendingModalOpen = false;

        $payload = $trxId ? $this->buildPrintPayload($trxId) : null;
        if ($payload) {
            $this->dispatch('pos-print-modal', payload: $payload, context: 'pending');
        }

        $this->resetOrderForNewTransaction();
    }

    public function deletePending(int $transactionId): void
    {
        $this->authorize('transactions.void');

        $deleted = DB::transaction(function () use ($transactionId) {
            $trx = Transaction::query()
                ->whereKey($transactionId)
                ->where('channel', 'pos')
                ->where('payment_status', 'pending')
                ->lockForUpdate()
                ->first();

            if (! $trx) {
                return false;
            }

            TransactionItem::query()->where('transaction_id', $trx->id)->delete();
            $trx->delete();

            return true;
        });

        if (! $deleted) {
            return;
        }

        if ($this->editingTransactionId === (int) $transactionId) {
            $this->resetOrderForNewTransaction();
        }

        $this->dispatch('toast', type: 'success', message: 'Pesanan pending berhasil dihapus.');
    }

    public function checkout(): void
    {
        if (count($this->cartItems) === 0) {
            return;
        }

        if ($this->cartLocked && $this->editingTransactionId !== null) {
            $this->reloadCartItemsFromTransaction((int) $this->editingTransactionId);
        } else {
            $this->applyVariantPricesToCartItems();
        }

        $this->recalculateTotals();

        if (! $this->ensureManualDiscountValid()) {
            return;
        }

        $member = $this->memberId ? Member::query()->find($this->memberId) : null;
        $guestId = $member ? null : ($this->customerPhone ? trim((string) $this->customerPhone) : null);

        $voucherResolved = null;
        if (trim((string) $this->voucherCodeInput) !== '') {
            $voucherResolved = app(\App\Services\Vouchers\VoucherEligibilityService::class)
                ->validate((string) $this->voucherCodeInput, $member, $this->cartItems, $guestId);

            if (! (bool) ($voucherResolved['ok'] ?? false)) {
                $this->dispatch('toast', type: 'error', message: (string) ($voucherResolved['message'] ?? 'Voucher tidak bisa digunakan.'));

                return;
            }
        }

        $trxId = null;
        $previousPaymentStatus = null;
        $validated = $this->validate([
            'customerName' => ['required', 'string', 'max:255'],
            'customerPhone' => ['nullable', 'string', 'max:50'],
            'paymentMethod' => ['required', 'string', 'max:50'],
            'cashReceived' => ['nullable', 'string', 'max:50'],
            'taxRate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $manualTypeForPermission = $this->manualDiscountType !== null ? trim((string) $this->manualDiscountType) : '';
        $manualValueForPermission = $this->manualDiscountValue === null ? 0 : (int) $this->manualDiscountValue;

        if (($manualValueForPermission > 0 || $manualTypeForPermission !== '') && ! $this->userHasManualDiscountPermission()) {
            $this->addError('manualDiscountType', 'Anda tidak memiliki izin untuk memberikan diskon manual.');

            return;
        }

        if ($this->orderType === 'dine_in' && ! $this->selectedTableId) {
            $this->dispatch('toast', type: 'error', message: 'Order dine-in wajib memilih meja.');

            return;
        }

        $isCash = $validated['paymentMethod'] === 'cash';
        $cashReceived = $isCash ? (int) preg_replace('/\D+/', '', (string) ($validated['cashReceived'] ?? '0')) : null;

        if ($isCash && ($cashReceived === null || $cashReceived < $this->total)) {
            $this->addError('cashReceived', 'Uang diterima kurang dari total.');

            return;
        }

        DB::transaction(function () use ($validated, $isCash, $cashReceived, $voucherResolved, $member, $guestId, &$trxId, &$previousPaymentStatus) {
            $trx = $this->editingTransactionId
                ? Transaction::query()->whereKey($this->editingTransactionId)->lockForUpdate()->first()
                : null;

            $manualType = $this->manualDiscountAmount > 0 ? $this->manualDiscountType : null;
            $manualValue = $this->manualDiscountAmount > 0 ? $this->manualDiscountValue : null;
            $manualNote = $this->manualDiscountAmount > 0 ? $this->manualDiscountNote : null;

            $voucherCampaignId = null;
            $voucherCodeId = null;
            $voucherCode = null;

            if (is_array($voucherResolved) && (bool) ($voucherResolved['ok'] ?? false)) {
                $codeRow = $voucherResolved['voucher_code'] ?? null;
                if ($codeRow instanceof VoucherCode) {
                    $locked = VoucherCode::query()->whereKey($codeRow->id)->with('campaign')->lockForUpdate()->first();
                    if (! $locked || ! (bool) $locked->is_active) {
                        throw new \RuntimeException('Voucher tidak aktif.');
                    }

                    $limitTotal = $locked->usage_limit_total ?? $locked->campaign?->usage_limit_total;
                    if ($limitTotal !== null && (int) $locked->times_redeemed >= (int) $limitTotal) {
                        throw new \RuntimeException('Kuota voucher habis.');
                    }

                    $limitUser = $locked->usage_limit_per_user ?? $locked->campaign?->usage_limit_per_user;
                    if ($limitUser !== null) {
                        $q = VoucherRedemption::query()->where('voucher_code_id', (int) $locked->id);
                        if ($member) {
                            $q->where('member_id', (int) $member->id);
                        } else {
                            $q->where('guest_identifier', (string) $guestId);
                        }
                        if ((int) $q->count() >= (int) $limitUser) {
                            throw new \RuntimeException('Kuota voucher untuk customer habis.');
                        }
                    }

                    $voucherCampaignId = (int) $locked->voucher_campaign_id;
                    $voucherCodeId = (int) $locked->id;
                    $voucherCode = (string) $locked->code;

                    $locked->increment('times_redeemed');
                }
            }

            if (! $trx) {
                $trx = Transaction::query()->create([
                    'code' => Transaction::generateUniqueCode(),
                    'member_id' => $this->memberId,
                    'channel' => 'pos',
                    'name' => $validated['customerName'],
                    'phone' => $validated['customerPhone'] !== '' ? $validated['customerPhone'] : null,
                    'email' => null,
                    'order_type' => $this->orderType,
                    'dining_table_id' => $this->orderType === 'dine_in' ? $this->selectedTableId : null,
                    'voucher_campaign_id' => $voucherCampaignId,
                    'voucher_code_id' => $voucherCodeId,
                    'voucher_code' => $voucherCode,
                    'subtotal' => $this->subtotal,
                    'voucher_discount_amount' => $this->voucherDiscountAmount,
                    'manual_discount_type' => $manualType,
                    'manual_discount_value' => $manualValue,
                    'manual_discount_amount' => $this->manualDiscountAmount,
                    'manual_discount_note' => $manualNote,
                    'manual_discount_by_user_id' => auth()->id(),
                    'discount_total_amount' => $this->discountTotalAmount,
                    'point_discount_amount' => 0,
                    'points_redeemed' => 0,
                    'points_earned' => 0,
                    'tax_percentage' => $this->taxRate,
                    'tax_amount' => $this->taxAmount,
                    'rounding_amount' => $this->roundingAmount,
                    'cash_received' => $cashReceived,
                    'cash_change' => $isCash ? max(0, $cashReceived - $this->total) : null,
                    'total' => $this->total,
                    'checkout_link' => '',
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => 'pending',
                    'order_status' => 'new',
                    'external_id' => Transaction::generateUniqueCode(10),
                ]);
            } else {
                $trx->update([
                    'member_id' => $this->memberId,
                    'name' => $validated['customerName'],
                    'phone' => $validated['customerPhone'] !== '' ? $validated['customerPhone'] : null,
                    'order_type' => $this->orderType,
                    'dining_table_id' => $this->orderType === 'dine_in' ? $this->selectedTableId : null,
                    'voucher_campaign_id' => $voucherCampaignId,
                    'voucher_code_id' => $voucherCodeId,
                    'voucher_code' => $voucherCode,
                    'subtotal' => $this->subtotal,
                    'voucher_discount_amount' => $this->voucherDiscountAmount,
                    'manual_discount_type' => $manualType,
                    'manual_discount_value' => $manualValue,
                    'manual_discount_amount' => $this->manualDiscountAmount,
                    'manual_discount_note' => $manualNote,
                    'manual_discount_by_user_id' => auth()->id(),
                    'discount_total_amount' => $this->discountTotalAmount,
                    'tax_percentage' => $this->taxRate,
                    'tax_amount' => $this->taxAmount,
                    'rounding_amount' => $this->roundingAmount,
                    'cash_received' => $cashReceived,
                    'cash_change' => $isCash ? max(0, $cashReceived - $this->total) : null,
                    'total' => $this->total,
                    'payment_method' => $validated['paymentMethod'],
                    'payment_status' => 'pending',
                    'order_status' => 'new',
                ]);
                TransactionItem::query()->where('transaction_id', $trx->id)->delete();
            }

            $manualAllocations = $this->allocateManualDiscount($this->cartItems, $this->manualDiscountAmount, $this->voucherAllocations);

            $productIds = collect($this->cartItems)
                ->map(fn (array $row) => (int) ($row['product_id'] ?? 0))
                ->filter(fn (int $id) => $id > 0)
                ->unique()
                ->values()
                ->all();

            $productsById = $productIds === []
                ? collect()
                : Product::query()
                    ->with(['packageItems.componentVariant.product'])
                    ->whereIn('id', $productIds)
                    ->get()
                    ->keyBy('id');

            foreach ($this->cartItems as $index => $item) {
                $productId = (int) ($item['product_id'] ?? 0);
                $variantId = (int) ($item['variant_id'] ?? 0);
                $qty = (int) ($item['quantity'] ?? 0);
                $price = (int) ($item['price'] ?? 0);
                $note = $item['note'] ?? null;

                if ($productId <= 0 || $variantId <= 0 || $qty <= 0) {
                    continue;
                }

                $parent = TransactionItem::query()->create([
                    'transaction_id' => $trx->id,
                    'product_id' => $productId,
                    'product_variant_id' => $variantId,
                    'quantity' => $qty,
                    'price' => $price,
                    'subtotal' => $qty * $price,
                    'voucher_discount_amount' => (int) ($this->voucherAllocations[$index] ?? 0),
                    'manual_discount_amount' => (int) ($manualAllocations[$index] ?? 0),
                    'note' => $note === '' ? null : $note,
                ]);

                $product = $productsById->get($productId);

                if (! $product || ! $product->is_package) {
                    continue;
                }

                $this->createPackageChildItems($trx, $parent, $product, $item, $qty);
            }

            if ($voucherCodeId && $voucherCampaignId) {
                $campaign = is_array($voucherResolved) ? ($voucherResolved['campaign'] ?? null) : null;
                VoucherRedemption::query()->create([
                    'voucher_campaign_id' => $voucherCampaignId,
                    'voucher_code_id' => $voucherCodeId,
                    'transaction_id' => (int) $trx->id,
                    'member_id' => $this->memberId,
                    'guest_identifier' => $member ? null : $guestId,
                    'discount_amount' => $this->voucherDiscountAmount,
                    'snapshot' => [
                        'campaign' => [
                            'id' => $voucherCampaignId,
                            'name' => $campaign?->name ?? null,
                            'discount_type' => $campaign?->discount_type ?? null,
                            'discount_value' => $campaign?->discount_value ?? null,
                            'max_discount_amount' => $campaign?->max_discount_amount ?? null,
                            'min_eligible_subtotal' => $campaign?->min_eligible_subtotal ?? null,
                            'is_member_only' => (bool) ($campaign?->is_member_only ?? false),
                        ],
                    ],
                    'redeemed_at' => now(),
                ]);

                TransactionEvent::query()->create([
                    'transaction_id' => (int) $trx->id,
                    'actor_user_id' => auth()->id(),
                    'action' => 'voucher_redeem',
                    'meta' => [
                        'voucher_campaign_id' => $voucherCampaignId,
                        'voucher_code_id' => $voucherCodeId,
                        'voucher_code' => $voucherCode,
                        'discount_amount' => $this->voucherDiscountAmount,
                    ],
                ]);
            }

            if ($this->manualDiscountAmount > 0) {
                TransactionEvent::query()->create([
                    'transaction_id' => (int) $trx->id,
                    'actor_user_id' => auth()->id(),
                    'action' => 'manual_discount',
                    'meta' => [
                        'type' => $this->manualDiscountType,
                        'value' => $this->manualDiscountValue,
                        'amount' => $this->manualDiscountAmount,
                        'note' => $this->manualDiscountNote,
                        'approval_required' => false,
                    ],
                ]);
            }

            if ($this->pointsToRedeem > 0 && (int) ($trx->points_redeemed ?? 0) <= 0) {
                app(\App\Services\PointService::class)->redeemPoints($trx, $this->pointsToRedeem);

                TransactionEvent::query()->create([
                    'transaction_id' => (int) $trx->id,
                    'actor_user_id' => auth()->id(),
                    'action' => 'point_redeem',
                    'meta' => [
                        'points_redeemed' => $this->pointsToRedeem,
                        'point_discount_amount' => $this->pointDiscountAmount,
                    ],
                ]);
            }

            $previousPaymentStatus = (string) $trx->payment_status;

            $trx->forceFill([
                'payment_status' => 'paid',
            ])->save();

            $trxId = (int) $trx->id;
            $this->editingTransactionId = null;
        });

        if (! $trxId) {
            return;
        }

        $trx = Transaction::query()->whereKey($trxId)->with('diningTable')->first();
        if (! $trx) {
            return;
        }

        if ((string) $trx->channel === 'self_order' && (string) ($trx->self_order_token ?? '') !== '' && (string) $previousPaymentStatus !== 'paid') {
            event(new SelfOrderPaymentUpdated($trx));
        }

        $this->dispatch('toast', type: 'success', message: 'Transaksi berhasil disimpan.');
        $this->checkoutModalOpen = false;
        $this->clearCart();

        $payload = $this->buildPrintPayload($trxId);
        if ($payload) {
            $this->dispatch('pos-print-modal', payload: $payload, context: 'checkout');
        }
    }

    private function ensureManualDiscountValid(): bool
    {
        $type = $this->manualDiscountType !== null ? trim((string) $this->manualDiscountType) : '';
        $value = $this->manualDiscountValue === null ? null : (int) $this->manualDiscountValue;

        if (($value ?? 0) > 0 && $type === '') {
            $this->addError('manualDiscountType', 'Tipe diskon wajib dipilih.');

            return false;
        }

        if ($type !== '' && ! in_array($type, ['percent', 'fixed_amount'], true)) {
            $this->addError('manualDiscountType', 'Tipe diskon tidak valid.');

            return false;
        }

        if ($type !== '' && ($value === null || $value <= 0)) {
            $this->addError('manualDiscountValue', 'Nilai diskon wajib diisi.');

            return false;
        }

        if ($type === 'percent' && $value !== null && ($value < 0 || $value > 100)) {
            $this->addError('manualDiscountValue', 'Diskon persen harus 0 - 100.');

            return false;
        }

        if ($type === 'fixed_amount' && $value !== null && $value < 0) {
            $this->addError('manualDiscountValue', 'Nilai diskon tidak valid.');

            return false;
        }

        return true;
    }

    private function userHasManualDiscountPermission(): bool
    {
        $user = auth()->user();
        if (! $user) {
            return false;
        }

        if ($user->permissions()->where('name', 'discounts.manual.apply')->exists()) {
            return true;
        }

        return $user->roles()
            ->whereHas('permissions', fn ($q) => $q->where('name', 'discounts.manual.apply'))
            ->exists();
    }

    private function allocateManualDiscount(array $cartItems, int $manualDiscountAmount, array $voucherAllocations): array
    {
        $manualDiscountAmount = max(0, (int) $manualDiscountAmount);
        if ($manualDiscountAmount <= 0) {
            return [];
        }

        $bases = [];
        $sum = 0;

        foreach ($cartItems as $index => $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (int) ($item['price'] ?? 0);
            $subtotal = $qty > 0 && $price >= 0 ? $qty * $price : 0;
            $voucher = (int) ($voucherAllocations[$index] ?? 0);
            $base = max(0, $subtotal - $voucher);

            $bases[$index] = $base;
            $sum += $base;
        }

        if ($sum <= 0) {
            return [];
        }

        $allocations = [];
        $remainders = [];
        $allocated = 0;

        foreach ($bases as $index => $base) {
            $raw = ($manualDiscountAmount * $base) / $sum;
            $floor = (int) floor($raw);
            $allocations[$index] = $floor;
            $remainders[$index] = $raw - $floor;
            $allocated += $floor;
        }

        $left = $manualDiscountAmount - $allocated;
        if ($left > 0) {
            arsort($remainders);
            foreach (array_keys($remainders) as $index) {
                if ($left <= 0) {
                    break;
                }
                $allocations[$index] = (int) $allocations[$index] + 1;
                $left--;
            }
        }

        return $allocations;
    }

    private function createPackageChildItems(Transaction $trx, TransactionItem $parent, Product $product, array $cartItem, int $parentQty): void
    {
        $packageType = (string) ($product->package_type ?? 'simple');

        if ($packageType === 'complex') {
            $components = $cartItem['package_components'] ?? [];
            if (! is_array($components)) {
                return;
            }

            $merged = [];
            foreach ($components as $row) {
                if (! is_array($row)) {
                    continue;
                }

                $componentProductId = (int) ($row['product_id'] ?? 0);
                $componentVariantId = (int) ($row['variant_id'] ?? 0);
                $componentBaseQty = (int) ($row['quantity'] ?? 0);
                $componentNote = $row['note'] ?? null;

                if ($componentProductId <= 0 || $componentVariantId <= 0 || $componentBaseQty <= 0) {
                    continue;
                }

                $note = $componentNote === '' ? null : $componentNote;
                $key = $componentProductId.'|'.$componentVariantId.'|'.trim((string) ($note ?? ''));

                if (! array_key_exists($key, $merged)) {
                    $merged[$key] = [
                        'product_id' => $componentProductId,
                        'variant_id' => $componentVariantId,
                        'base_qty' => 0,
                        'note' => $note,
                    ];
                }

                $merged[$key]['base_qty'] += $componentBaseQty;
            }

            foreach (array_values($merged) as $row) {
                $componentQty = $parentQty * (int) ($row['base_qty'] ?? 0);
                if ($componentQty <= 0) {
                    continue;
                }

                TransactionItem::query()->create([
                    'transaction_id' => $trx->id,
                    'parent_transaction_item_id' => (int) $parent->id,
                    'product_id' => (int) ($row['product_id'] ?? 0),
                    'product_variant_id' => (int) ($row['variant_id'] ?? 0),
                    'quantity' => $componentQty,
                    'price' => 0,
                    'subtotal' => 0,
                    'voucher_discount_amount' => 0,
                    'manual_discount_amount' => 0,
                    'note' => $row['note'] ?? null,
                ]);
            }

            return;
        }

        $note = $cartItem['note'] ?? null;

        foreach ($product->packageItems as $packageItem) {
            $componentVariant = $packageItem->componentVariant;
            if (! $componentVariant) {
                continue;
            }

            $componentQty = $parentQty * (int) $packageItem->quantity;
            if ($componentQty <= 0) {
                continue;
            }

            TransactionItem::query()->create([
                'transaction_id' => $trx->id,
                'parent_transaction_item_id' => (int) $parent->id,
                'product_id' => (int) $componentVariant->product_id,
                'product_variant_id' => (int) $componentVariant->id,
                'quantity' => $componentQty,
                'price' => 0,
                'subtotal' => 0,
                'voucher_discount_amount' => 0,
                'manual_discount_amount' => 0,
                'note' => $note === '' ? null : $note,
            ]);
        }
    }

    private function buildPrintPayload(int $transactionId): ?array
    {
        return app(PosPrintPayloadService::class)->build($transactionId);
    }

    public function updatedCashReceived(): void
    {
        $isCash = $this->paymentMethod === 'cash';
        if (! $isCash) {
            $this->cashChange = 0;

            return;
        }

        $cashReceived = (int) preg_replace('/\D+/', '', (string) ($this->cashReceived ?? '0'));
        $this->cashChange = max(0, $cashReceived - $this->total);
    }

    public function importTransactionCode(): void
    {
        $this->authorize('transactions.details');

        $code = trim($this->scanCode);
        if ($code === '') {
            return;
        }

        $trx = Transaction::query()
            ->with(['transactionItems.product', 'transactionItems.variant', 'diningTable'])
            ->where('code', $code)
            ->where('payment_status', 'pending')
            ->first();

        if (! $trx) {
            $this->dispatch('toast', type: 'error', message: 'Transaksi tidak ditemukan atau sudah dibayar.');
            $this->scanCode = '';

            return;
        }

        $this->editingTransactionId = (int) $trx->id;
        $this->orderType = (string) $trx->order_type;
        $this->selectedTableId = $trx->dining_table_id === null ? null : (int) $trx->dining_table_id;
        $this->memberId = $trx->member_id === null ? null : (int) $trx->member_id;
        $this->customerName = (string) $trx->name;
        $this->customerPhone = $trx->phone;
        $this->cartLocked = (string) $trx->channel === 'self_order';

        $this->lockedVoucherCode = $trx->voucher_code === null ? null : (string) $trx->voucher_code;
        $this->lockedVoucherDiscountAmount = (int) ($trx->voucher_discount_amount ?? 0);
        $this->lockedVoucherAllocations = $trx->transactionItems->mapWithKeys(function (TransactionItem $item, int $index): array {
            return [$index => (int) ($item->voucher_discount_amount ?? 0)];
        })->all();
        $this->lockedPointsToRedeem = (int) ($trx->points_redeemed ?? 0);
        $this->lockedPointDiscountAmount = (int) ($trx->point_discount_amount ?? 0);

        $this->cartItems = $trx->transactionItems->map(function (TransactionItem $item) {
            $name = $item->product ? (string) $item->product->name : 'Produk';
            $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
            $price = (int) round((float) $item->price);

            return [
                'product_id' => (int) $item->product_id,
                'variant_id' => $item->product_variant_id === null ? 0 : (int) $item->product_variant_id,
                'name' => $name,
                'variant_name' => $variantName,
                'price' => $price,
                'original_price' => $price,
                'percent' => null,
                'quantity' => (int) $item->quantity,
                'note' => $item->note,
            ];
        })->all();

        $this->taxRate = $trx->tax_percentage === null ? $this->taxRate : (float) $trx->tax_percentage;
        $this->voucherCodeInput = $this->lockedVoucherCode;
        $this->recalculateTotals();

        $this->scanCode = '';
        $this->dispatch('toast', type: 'success', message: 'Transaksi dimuat.');
    }

    public function updatedTaxRate(): void
    {
        $this->recalculateTotals();
    }

    public function getPendingTransactionsProperty()
    {
        if (! auth()->user()?->can('transactions.view')) {
            return collect();
        }

        return Transaction::query()
            ->where('channel', 'pos')
            ->where('payment_status', 'pending')
            ->with(['diningTable'])
            ->withCount('transactionItems')
            ->orderByDesc('updated_at')
            ->limit(20)
            ->get();
    }

    public function getSelfOrderPaidUnprocessedProperty()
    {
        if (! auth()->user()?->can('transactions.view')) {
            return collect();
        }

        return Transaction::query()
            ->where('channel', 'self_order')
            ->where('payment_method', 'qris_midtrans')
            ->where('payment_status', 'paid')
            ->where('is_midtrans_processed', false)
            ->with(['diningTable'])
            ->withCount('transactionItems')
            ->latest('paid_at')
            ->limit(30)
            ->get();
    }

    public function getSelfOrderCashPendingProperty()
    {
        if (! auth()->user()?->can('transactions.view')) {
            return collect();
        }

        return Transaction::query()
            ->where('channel', 'self_order')
            ->where('payment_method', 'cash')
            ->where('payment_status', 'pending')
            ->with(['diningTable'])
            ->withCount('transactionItems')
            ->orderByDesc('updated_at')
            ->limit(30)
            ->get();
    }

    public function getCategoriesProperty()
    {
        return Category::query()->orderBy('name')->get(['id', 'name']);
    }

    public function getTablesProperty(): array
    {
        return DiningTable::query()
            ->orderBy('table_number')
            ->get(['id', 'table_number'])
            ->map(fn (DiningTable $t) => [
                'id' => (int) $t->id,
                'label' => 'Meja '.$t->table_number,
                'number' => (string) $t->table_number,
            ])
            ->all();
    }

    public function getMembersProperty()
    {
        if (! auth()->user()?->can('members.view')) {
            return collect();
        }

        return Member::query()
            ->orderBy('name')
            ->limit(200)
            ->get(['id', 'name', 'phone']);
    }

    public function getProductsProperty()
    {
        $term = trim($this->search);

        $products = Product::query()
            ->where('is_available', true)
            ->when($this->selectedCategoryId, fn (Builder $q) => $q->where('category_id', $this->selectedCategoryId))
            ->when($term !== '', function (Builder $q) use ($term) {
                $like = '%'.$term.'%';
                $q->where(function (Builder $qq) use ($like) {
                    $qq->where('name', 'like', $like)->orWhere('description', 'like', $like);
                });
            })
            ->with(['variants' => function ($q) {
                $q->orderBy('id');
            }])
            ->orderBy('name')
            ->limit(120)
            ->get();

        return $products;
    }

    private function finalVariantPrice(ProductVariant $variant): int
    {
        $base = (int) round((float) $variant->price);
        $after = $variant->price_afterdiscount === null ? null : (int) round((float) $variant->price_afterdiscount);
        if ($after !== null && $after > 0 && $after < $base) {
            return $after;
        }

        $percent = $variant->percent === null ? 0 : (int) $variant->percent;
        if ($percent > 0 && $percent < 100) {
            $computed = (int) round($base - ($base * ($percent / 100)));

            return max(0, $computed);
        }

        return $base;
    }

    private function applyVariantPricesToCartItems(): void
    {
        $variantIds = collect($this->cartItems)
            ->filter(fn ($row) => is_array($row))
            ->map(fn (array $row) => (int) ($row['variant_id'] ?? 0))
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        if ($variantIds === []) {
            $this->cartItems = [];

            return;
        }

        $variants = ProductVariant::query()
            ->with('product')
            ->whereIn('id', $variantIds)
            ->get()
            ->keyBy('id');

        $normalized = [];
        foreach ($this->cartItems as $row) {
            if (! is_array($row)) {
                continue;
            }

            $variantId = (int) ($row['variant_id'] ?? 0);
            $qty = (int) ($row['quantity'] ?? 0);

            if ($variantId <= 0 || $qty <= 0) {
                continue;
            }

            $variant = $variants->get($variantId);
            if (! $variant || ! $variant->product) {
                continue;
            }

            $final = $this->finalVariantPrice($variant);
            $base = (int) round((float) $variant->price);

            $row['product_id'] = (int) $variant->product->id;
            $row['name'] = (string) $variant->product->name;
            $row['variant_name'] = ItemNameFormatter::displayVariantName((int) $variant->product->id, (string) $variant->name);
            $row['price'] = $final;
            $row['original_price'] = $base;
            $row['percent'] = $variant->percent === null ? null : (int) $variant->percent;
            $row['quantity'] = $qty;

            if (array_key_exists('note', $row) && $row['note'] === '') {
                $row['note'] = null;
            }

            $normalized[] = $row;
        }

        $this->cartItems = array_values($normalized);
    }

    private function reloadCartItemsFromTransaction(int $transactionId): void
    {
        $trx = Transaction::query()
            ->with(['transactionItems.product', 'transactionItems.variant'])
            ->whereKey($transactionId)
            ->where('payment_status', 'pending')
            ->first();

        if (! $trx) {
            return;
        }

        $displayItems = $trx->transactionItems
            ->whereNull('parent_transaction_item_id')
            ->values();

        $this->cartItems = $displayItems->map(function (TransactionItem $item) use ($trx) {
            $name = $item->product ? (string) $item->product->name : 'Produk';
            $variantName = ItemNameFormatter::displayVariantName((int) $item->product_id, $item->variant?->name);
            $price = (int) round((float) $item->price);

            $payload = [
                'product_id' => (int) $item->product_id,
                'variant_id' => $item->product_variant_id === null ? 0 : (int) $item->product_variant_id,
                'name' => $name,
                'variant_name' => $variantName,
                'price' => $price,
                'original_price' => $price,
                'percent' => null,
                'quantity' => (int) $item->quantity,
                'note' => $item->note,
            ];

            if ($item->product && (bool) $item->product->is_package && (string) ($item->product->package_type ?? 'simple') === 'complex') {
                $children = $trx->transactionItems
                    ->where('parent_transaction_item_id', (int) $item->id)
                    ->values();

                $parentQty = (int) $item->quantity;
                $payload['package_type'] = 'complex';
                $payload['package_components'] = $children->map(fn (TransactionItem $child) => [
                    'product_id' => (int) $child->product_id,
                    'variant_id' => $child->product_variant_id === null ? 0 : (int) $child->product_variant_id,
                    'quantity' => $parentQty > 0 ? (int) max(1, (int) round(((int) $child->quantity) / $parentQty)) : (int) $child->quantity,
                    'note' => $child->note,
                ])->all();
            }

            return $payload;
        })->all();
    }

    private function recalculateTotals(): void
    {
        $subtotal = 0;
        foreach ($this->cartItems as $item) {
            $qty = (int) ($item['quantity'] ?? 0);
            $price = (int) ($item['price'] ?? 0);
            if ($qty > 0 && $price >= 0) {
                $subtotal += $qty * $price;
            }
        }

        $this->subtotal = max(0, $subtotal);

        $this->voucherValid = false;
        $this->voucherDiscountAmount = 0;
        $this->voucherMessage = '';
        $this->voucherAllocations = [];

        $isLockedTransaction = $this->cartLocked && $this->editingTransactionId !== null;
        if ($isLockedTransaction) {
            $code = trim((string) ($this->lockedVoucherCode ?? ''));
            $this->voucherCodeInput = $code !== '' ? $code : null;
            $this->voucherValid = $code !== '' && $this->lockedVoucherDiscountAmount > 0;
            $this->voucherDiscountAmount = max(0, (int) $this->lockedVoucherDiscountAmount);
            $this->voucherAllocations = (array) $this->lockedVoucherAllocations;
            $this->voucherMessage = $this->voucherValid ? 'Voucher diterapkan.' : '';
        } else {
            $code = trim((string) ($this->voucherCodeInput ?? ''));
            if ($code !== '' && $this->subtotal > 0) {
                $member = null;
                if ($this->memberId) {
                    $member = Member::query()->find($this->memberId);
                }
                $guestId = $member ? null : ($this->customerPhone ? trim((string) $this->customerPhone) : null);

                $elig = app(\App\Services\Vouchers\VoucherEligibilityService::class)
                    ->validate($code, $member, $this->cartItems, $guestId);

                if ((bool) ($elig['ok'] ?? false)) {
                    $this->voucherValid = true;
                    $this->voucherMessage = (string) ($elig['message'] ?? 'Voucher dapat digunakan.');
                    $this->voucherDiscountAmount = (int) ($elig['discount_amount'] ?? 0);

                    $allocations = (array) ($elig['allocations'] ?? []);
                    $eligibleLines = (array) ($elig['eligible_lines'] ?? []);
                    $byIndex = [];
                    foreach ($eligibleLines as $i => $line) {
                        $idx = (int) ($line['index'] ?? -1);
                        if ($idx >= 0) {
                            $byIndex[$idx] = (int) ($allocations[$i] ?? 0);
                        }
                    }
                    $this->voucherAllocations = $byIndex;
                } else {
                    $this->voucherMessage = (string) ($elig['message'] ?? 'Voucher tidak bisa digunakan.');
                }
            }
        }

        $this->manualDiscountAmount = 0;

        $manualType = $this->manualDiscountType ? (string) $this->manualDiscountType : null;
        $manualValue = $this->manualDiscountValue === null ? null : (int) $this->manualDiscountValue;

        if ($manualType !== null && $manualValue !== null && $manualValue > 0) {
            $base = max(0, $this->subtotal - $this->voucherDiscountAmount);

            if ($manualType === 'percent') {
                $pct = max(0, min(100, $manualValue));
                $this->manualDiscountAmount = (int) round($base * ($pct / 100));
            } elseif ($manualType === 'fixed_amount') {
                $this->manualDiscountAmount = min($base, max(0, $manualValue));
            } else {
                $this->manualDiscountAmount = 0;
            }
        }

        $this->pointDiscountAmount = 0;
        $this->pointsToRedeem = 0;

        if ($isLockedTransaction) {
            $this->pointsToRedeem = max(0, (int) $this->lockedPointsToRedeem);
            $this->pointDiscountAmount = max(0, (int) $this->lockedPointDiscountAmount);
            $this->redeemPoints = $this->pointsToRedeem > 0;
        } elseif ($this->redeemPoints && $this->memberPoints >= $this->minRedemptionPoints && $this->pointRedemptionValue > 0) {
            $baseForPoints = max(0, $this->subtotal - $this->voucherDiscountAmount - $this->manualDiscountAmount);
            if ($baseForPoints > 0) {
                // Calculate max points needed to cover the base amount
                $maxPointsNeeded = (int) floor($baseForPoints / $this->pointRedemptionValue);

                // Use the lesser of member points or max needed
                $pointsToUse = min($this->memberPoints, $maxPointsNeeded);

                $this->pointsToRedeem = $pointsToUse;
                $this->pointDiscountAmount = (int) ($pointsToUse * $this->pointRedemptionValue);

                // Cap at base amount just in case rounding causes issues
                $this->pointDiscountAmount = min($this->pointDiscountAmount, $baseForPoints);
            }
        }

        $this->discountTotalAmount = max(0, $this->voucherDiscountAmount + $this->manualDiscountAmount + $this->pointDiscountAmount);
        $netSubtotal = max(0, $this->subtotal - $this->discountTotalAmount);
        $this->netSubtotal = $netSubtotal;

        $taxBase = $this->discountAppliesBeforeTax ? $netSubtotal : $this->subtotal;
        $this->taxAmount = (int) round($taxBase * ((float) ($this->taxRate) / 100));

        $rawTotal = $netSubtotal + $this->taxAmount;
        if ($this->roundingBase <= 0) {
            $this->roundingAmount = 0;
            $this->total = $rawTotal;
        } else {
            $rounded = (int) (round($rawTotal / $this->roundingBase) * $this->roundingBase);
            $this->roundingAmount = $rounded - $rawTotal;
            $this->total = $rawTotal + $this->roundingAmount;
        }

        $this->updatedCashReceived();
    }

    public function render(): View
    {
        $this->authorize('pos.access');

        return view('livewire.pos.pos-page')->layout('layouts.app', ['title' => $this->title]);
    }
}
