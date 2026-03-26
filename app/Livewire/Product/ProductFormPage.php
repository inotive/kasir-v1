<?php

namespace App\Livewire\Product;

use App\Models\Category;
use App\Models\Ingredient;
use App\Models\PrinterSource;
use App\Models\Product;
use App\Models\ProductComplexPackageItem;
use App\Models\ProductPackageItem;
use App\Models\ProductVariant;
use App\Models\ProductVariantRecipe;
use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductFormPage extends Component
{
    use WithFileUploads;

    public string $title = 'Tambah Produk';

    public ?int $productId = null;

    public string $name = '';

    public string $description = '';

    public ?int $categoryId = null;

    public ?int $printerSourceId = null;

    public bool $isAvailable = true;

    public bool $isPromo = false;

    public bool $isFavorite = false;

    public bool $isPackage = false;

    public string $packageType = 'simple';

    public $image = null;

    public string $existingImage = '';

    public array $variants = [];

    public array $variantRecipes = [];

    public array $packageItems = [];

    public array $complexPackageItems = [];

    protected array $validationAttributes = [
        'variantRecipes.*.*.ingredient_id' => 'Bahan baku',
        'variantRecipes.*.*.quantity' => 'Qty / porsi',
    ];

    private function kitchenPrinterSourcesQuery()
    {
        return PrinterSource::query()->whereRaw('LOWER(type) = ?', ['dapur']);
    }

    public function mount(?Product $product = null): void
    {
        if (! $product) {
            $this->title = 'Tambah Produk';
            $variant = $this->makeEmptyVariant();
            $this->variants = [$variant];
            $this->variantRecipes = [
                (string) $variant['key'] => [],
            ];
            $this->packageItems = [];
            $this->complexPackageItems = [];

            $this->printerSourceId = null;

            return;
        }

        $product->load(['variants.recipes', 'packageItems.componentVariant.product', 'complexPackageItems.componentProduct']);

        $this->productId = (int) $product->id;
        $this->title = 'Edit Produk';
        $this->name = (string) $product->name;
        $this->description = (string) $product->description;
        $this->categoryId = (int) $product->category_id;
        $this->printerSourceId = $product->printer_source_id === null ? null : (int) $product->printer_source_id;
        $this->isAvailable = (bool) $product->is_available;
        $this->isPromo = (bool) $product->is_promo;
        $this->isFavorite = (bool) $product->is_favorite;
        $this->isPackage = (bool) $product->is_package;
        $this->packageType = $this->isPackage ? ((string) ($product->package_type ?? 'simple')) : 'simple';
        if (! $this->isPackage && $this->printerSourceId !== null) {
            $isKitchen = $this->kitchenPrinterSourcesQuery()
                ->where('id', $this->printerSourceId)
                ->exists();
            if (! $isKitchen) {
                $this->printerSourceId = null;
            }
        }
        $this->existingImage = (string) $product->image;

        $this->variants = $product->variants
            ->map(fn ($variant) => [
                'id' => (int) $variant->id,
                'key' => (string) Str::uuid(),
                'name' => (string) $variant->name,
                'price' => (string) $variant->price,
                'percent' => $variant->percent === null ? null : (int) $variant->percent,
            ])
            ->values()
            ->all();

        if ($this->variants === []) {
            $variant = $this->makeEmptyVariant();
            $this->variants = [$variant];
        }

        $this->variantRecipes = [];

        $variantsById = $product->variants->keyBy('id');

        foreach ($this->variants as $variantState) {
            $variantId = (int) ($variantState['id'] ?? 0);
            $variantKey = (string) ($variantState['key'] ?? '');

            $variant = $variantsById->get($variantId);
            if (! $variant) {
                $this->variantRecipes[$variantKey] = [];

                continue;
            }

            $this->variantRecipes[$variantKey] = $variant->recipes
                ->map(fn ($recipe) => [
                    'id' => (int) $recipe->id,
                    'key' => (string) Str::uuid(),
                    'ingredient_id' => (int) $recipe->ingredient_id,
                    'quantity' => QuantityFormatter::format((float) $recipe->quantity),
                ])
                ->values()
                ->all();
        }

        $this->packageItems = $product->packageItems
            ->map(fn (ProductPackageItem $item) => [
                'id' => (int) $item->id,
                'key' => (string) Str::uuid(),
                'component_variant_id' => (int) $item->component_product_variant_id,
                'quantity' => (int) $item->quantity,
            ])
            ->values()
            ->all();

        $this->complexPackageItems = $product->complexPackageItems
            ->map(fn (ProductComplexPackageItem $item) => [
                'id' => (int) $item->id,
                'key' => (string) Str::uuid(),
                'component_product_id' => (int) $item->component_product_id,
                'quantity' => (int) $item->quantity,
                'is_splitable' => (bool) $item->is_splitable,
            ])
            ->values()
            ->all();
    }

    protected function rules(): array
    {
        $imageRules = $this->productId
            ? ['nullable', 'image', 'max:2048']
            : ['required', 'image', 'max:2048'];

        $hasKitchenSources = $this->kitchenPrinterSourcesQuery()->exists();
        $kitchenExistsRule = Rule::exists('printer_sources', 'id')
            ->where(fn ($q) => $q->whereRaw('LOWER(type) = ?', ['dapur']));

        $printerSourceRules = $hasKitchenSources
            ? ['nullable', 'integer', $kitchenExistsRule]
            : ['nullable', 'integer'];

        if ($this->isPackage) {
            $printerSourceRules = $hasKitchenSources
                ? ['nullable', 'integer', $kitchenExistsRule]
                : ['nullable', 'integer'];
        }

        $packageTypeRules = $this->isPackage
            ? ['required', 'string', Rule::in(['simple', 'complex'])]
            : ['nullable', 'string'];

        $packageItemsRules = $this->isPackage && $this->packageType === 'simple'
            ? ['required', 'array', 'min:1']
            : ['array'];

        $complexPackageItemsRules = $this->isPackage && $this->packageType === 'complex'
            ? ['required', 'array', 'min:1']
            : ['array'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'categoryId' => ['required', 'integer', 'exists:categories,id'],
            'printerSourceId' => $printerSourceRules,
            'isAvailable' => ['boolean'],
            'isPromo' => ['boolean'],
            'isFavorite' => ['boolean'],
            'isPackage' => ['boolean'],
            'packageType' => $packageTypeRules,
            'image' => $imageRules,
            'variants' => ['required', 'array', 'min:1'],
            'variants.*.id' => ['nullable', 'integer', 'exists:product_variants,id', 'distinct'],
            'variants.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'variants.*.name' => ['required', 'string', 'max:255'],
            'variants.*.price' => ['required', 'integer', 'min:0'],
            'variants.*.percent' => ['nullable', 'integer', 'min:0', 'max:100'],
            'variantRecipes' => ['array'],
            'variantRecipes.*' => ['array'],
            'variantRecipes.*.*.id' => ['nullable', 'integer', 'exists:product_variant_recipes,id', 'distinct'],
            'variantRecipes.*.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'variantRecipes.*.*.ingredient_id' => ['required', 'integer', 'exists:ingredients,id'],
            'variantRecipes.*.*.quantity' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null) {
                    $fail('Qty / porsi tidak valid.');

                    return;
                }

                if ($parsed < 0.001) {
                    $fail('Qty / porsi minimal 0,001.');
                }
            }],
            'packageItems' => $packageItemsRules,
            'packageItems.*.id' => ['nullable', 'integer', 'exists:product_package_items,id', 'distinct'],
            'packageItems.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'packageItems.*.component_variant_id' => [Rule::requiredIf($this->isPackage && $this->packageType === 'simple'), 'integer', 'exists:product_variants,id'],
            'packageItems.*.quantity' => [Rule::requiredIf($this->isPackage && $this->packageType === 'simple'), 'integer', 'min:1'],
            'complexPackageItems' => $complexPackageItemsRules,
            'complexPackageItems.*.id' => ['nullable', 'integer', 'exists:product_complex_package_items,id', 'distinct'],
            'complexPackageItems.*.key' => ['required', 'string', 'max:255', 'distinct'],
            'complexPackageItems.*.component_product_id' => [
                Rule::requiredIf($this->isPackage && $this->packageType === 'complex'),
                'integer',
                Rule::exists('products', 'id')->where(fn ($q) => $q->where('is_package', false)),
                'distinct',
            ],
            'complexPackageItems.*.quantity' => [Rule::requiredIf($this->isPackage && $this->packageType === 'complex'), 'integer', 'min:1'],
            'complexPackageItems.*.is_splitable' => ['boolean'],
        ];
    }

    public function addPackageItem(): void
    {
        $this->packageItems[] = $this->makeEmptyPackageItem();
    }

    public function removePackageItem(string $key): void
    {
        $this->packageItems = collect($this->packageItems)
            ->reject(fn (array $row) => (string) ($row['key'] ?? '') === $key)
            ->all();
    }

    public function addComplexPackageItem(): void
    {
        $this->complexPackageItems[] = $this->makeEmptyComplexPackageItem();
    }

    public function removeComplexPackageItem(string $key): void
    {
        $this->complexPackageItems = collect($this->complexPackageItems)
            ->reject(fn (array $row) => (string) ($row['key'] ?? '') === $key)
            ->all();
    }

    public function updatedIsPackage(bool $value): void
    {
        if (! $value) {
            $this->packageItems = [];
            $this->complexPackageItems = [];
            $this->packageType = 'simple';

            return;
        }

        $this->printerSourceId = null;
        if ($this->packageType === '') {
            $this->packageType = 'simple';
        }

        if ($this->packageType === 'simple' && $this->packageItems === []) {
            $this->packageItems = [$this->makeEmptyPackageItem()];
        }

        if ($this->packageType === 'complex' && $this->complexPackageItems === []) {
            $this->complexPackageItems = [$this->makeEmptyComplexPackageItem()];
        }
    }

    public function updatedPackageType(string $value): void
    {
        if (! $this->isPackage) {
            $this->packageType = 'simple';

            return;
        }

        if ($value === 'simple') {
            $this->complexPackageItems = [];
            if ($this->packageItems === []) {
                $this->packageItems = [$this->makeEmptyPackageItem()];
            }
        }

        if ($value === 'complex') {
            $this->packageItems = [];
            if ($this->complexPackageItems === []) {
                $this->complexPackageItems = [$this->makeEmptyComplexPackageItem()];
            }
        }
    }

    public function addVariant(): void
    {
        $variant = $this->makeEmptyVariant();
        $this->variants[] = $variant;
        $this->variantRecipes[(string) $variant['key']] = [];
    }

    public function removeVariant(string $key): void
    {
        $variantToRemove = collect($this->variants)->first(fn (array $row) => (string) ($row['key'] ?? '') === $key);
        $variantId = is_array($variantToRemove) && array_key_exists('id', $variantToRemove) ? (int) ($variantToRemove['id'] ?? 0) : 0;

        if ($variantId > 0 && $this->productId !== null) {
            $used = ProductVariant::query()
                ->where('product_id', $this->productId)
                ->whereKey($variantId)
                ->whereHas('transactionItems')
                ->exists();

            if ($used) {
                throw ValidationException::withMessages([
                    'variants' => 'Varian ini tidak bisa dihapus karena sudah digunakan pada transaksi.',
                ]);
            }
        }

        unset($this->variantRecipes[$key]);

        $this->variants = collect($this->variants)
            ->reject(fn (array $variant) => ($variant['key'] ?? '') === $key)
            ->all();

        if ($this->variants === []) {
            $variant = $this->makeEmptyVariant();
            $this->variants = [$variant];
            $this->variantRecipes = [
                (string) $variant['key'] => [],
            ];
        }
    }

    public function addRecipe(string $variantKey): void
    {
        if ($this->isPackage) {
            return;
        }

        if (! array_key_exists($variantKey, $this->variantRecipes)) {
            $this->variantRecipes[$variantKey] = [];
        }

        $this->variantRecipes[$variantKey][] = $this->makeEmptyRecipe();
    }

    public function removeRecipe(string $variantKey, string $recipeKey): void
    {
        if ($this->isPackage) {
            return;
        }

        if (! array_key_exists($variantKey, $this->variantRecipes)) {
            return;
        }

        $this->variantRecipes[$variantKey] = collect($this->variantRecipes[$variantKey])
            ->reject(fn (array $recipe) => (string) ($recipe['key'] ?? '') === $recipeKey)
            ->all();
    }

    public function save(): void
    {
        $this->normalizeForValidation();

        $validated = $this->validate();

        $newImagePath = null;

        if ($this->image) {
            $newImagePath = $this->image->store('products', 'public');
        }

        try {
            DB::transaction(function () use ($validated, $newImagePath) {
                $imagePath = $newImagePath ?? $this->existingImage;
                $isPackage = (bool) ($validated['isPackage'] ?? false);

                $payload = [
                    'name' => $validated['name'],
                    'description' => $validated['description'],
                    'category_id' => (int) $validated['categoryId'],
                    'printer_source_id' => $isPackage ? null : (array_key_exists('printerSourceId', $validated) ? ($validated['printerSourceId'] === null ? null : (int) $validated['printerSourceId']) : null),
                    'is_available' => (bool) $validated['isAvailable'],
                    'is_promo' => (bool) $validated['isPromo'],
                    'is_favorite' => (bool) $validated['isFavorite'],
                    'is_package' => $isPackage,
                    'package_type' => $isPackage ? (string) ($validated['packageType'] ?? 'simple') : null,
                    'image' => $imagePath,
                ];

                $product = $this->productId
                    ? Product::query()->findOrFail($this->productId)
                    : new Product;

                $product->fill($payload);
                $product->save();

                if ($this->productId === null) {
                    $this->productId = (int) $product->id;
                }

                $keptVariantIds = [];
                $variantKeyToId = [];

                foreach ($validated['variants'] as $variantPayload) {
                    $percent = $variantPayload['percent'] ?? null;
                    $price = (float) $variantPayload['price'];
                    $variantKey = (string) $variantPayload['key'];

                    $priceAfterDiscount = null;

                    if ($percent !== null) {
                        $priceAfterDiscount = (int) round($price - ($price * ((int) $percent / 100)));
                    }

                    $variantData = [
                        'name' => $variantPayload['name'],
                        'price' => $price,
                        'percent' => $percent === null ? null : (int) $percent,
                        'price_afterdiscount' => $priceAfterDiscount,
                    ];

                    $variantId = $variantPayload['id'] ?? null;

                    if ($variantId) {
                        $variant = $product->variants()->whereKey((int) $variantId)->firstOrFail();
                        $variant->update($variantData);
                        $keptVariantIds[] = (int) $variant->id;
                    } else {
                        $variant = $product->variants()->create($variantData);
                        $keptVariantIds[] = (int) $variant->id;
                    }

                    $variantKeyToId[$variantKey] = (int) $variant->id;
                }

                $existingVariantIds = $product->variants()->pluck('id')->map(fn ($id) => (int) $id)->all();
                $variantIdsToDelete = array_values(array_diff($existingVariantIds, $keptVariantIds));

                if ($variantIdsToDelete !== []) {
                    $blocked = $product->variants()
                        ->whereIn('id', $variantIdsToDelete)
                        ->withCount('transactionItems')
                        ->get()
                        ->firstWhere(fn ($variant) => (int) $variant->transaction_items_count > 0);

                    if ($blocked) {
                        throw ValidationException::withMessages([
                            'variants' => 'Ada varian yang tidak bisa dihapus karena sudah digunakan pada transaksi.',
                        ]);
                    }

                    $product->variants()->whereIn('id', $variantIdsToDelete)->delete();
                }

                $variantRecipes = $validated['variantRecipes'] ?? [];
                $variantIds = array_values($variantKeyToId);

                if ($variantIds !== []) {
                    ProductVariantRecipe::query()->whereIn('product_variant_id', $variantIds)->delete();
                }

                $allIngredientIds = [];

                foreach ($variantRecipes as $variantKey => $recipes) {
                    $variantId = $variantKeyToId[(string) $variantKey] ?? null;
                    if (! $variantId) {
                        continue;
                    }

                    $ingredientIds = array_map(fn ($row) => (int) ($row['ingredient_id'] ?? 0), $recipes);
                    $ingredientIds = array_filter($ingredientIds, fn ($id) => $id > 0);

                    if (count($ingredientIds) !== count(array_unique($ingredientIds))) {
                        throw ValidationException::withMessages([
                            'variantRecipes' => 'Bahan baku pada resep varian tidak boleh duplikat.',
                        ]);
                    }

                    $allIngredientIds = array_merge($allIngredientIds, $ingredientIds);

                    foreach ($recipes as $recipe) {
                        ProductVariantRecipe::query()->create([
                            'product_variant_id' => (int) $variantId,
                            'ingredient_id' => (int) $recipe['ingredient_id'],
                            'quantity' => (float) (QuantityParser::parse($recipe['quantity'] ?? null) ?? 0),
                        ]);
                    }
                }

                $allIngredientIds = array_values(array_unique($allIngredientIds));
                $ingredientCosts = $allIngredientIds === []
                    ? collect()
                    : Ingredient::query()
                        ->whereIn('id', $allIngredientIds)
                        ->pluck('cost_price', 'id');

                $packageItems = $validated['packageItems'] ?? [];
                $complexPackageItems = $validated['complexPackageItems'] ?? [];

                ProductPackageItem::query()
                    ->where('package_product_id', (int) $product->id)
                    ->delete();

                if ($isPackage) {
                    ProductComplexPackageItem::query()
                        ->where('package_product_id', (int) $product->id)
                        ->delete();

                    $packageType = (string) ($validated['packageType'] ?? 'simple');

                    if ($packageType === 'simple') {
                        $componentVariantIds = array_map(fn (array $row) => (int) ($row['component_variant_id'] ?? 0), $packageItems);
                        $componentVariantIds = array_filter($componentVariantIds, fn (int $id) => $id > 0);

                        if (count($componentVariantIds) !== count(array_unique($componentVariantIds))) {
                            throw ValidationException::withMessages([
                                'packageItems' => 'Isi paket tidak boleh duplikat untuk varian yang sama.',
                            ]);
                        }

                        $componentVariants = $componentVariantIds === []
                            ? collect()
                            : ProductVariant::query()
                                ->whereIn('id', $componentVariantIds)
                                ->get(['id', 'hpp']);

                        $hppByComponentVariantId = $componentVariants
                            ->keyBy('id')
                            ->map(fn (ProductVariant $v) => (int) ($v->hpp ?? 0))
                            ->all();

                        $packageHpp = 0;

                        foreach (array_values($packageItems) as $index => $row) {
                            $componentVariantId = (int) $row['component_variant_id'];
                            $qty = (int) $row['quantity'];
                            $packageHpp += max(0, (int) ($hppByComponentVariantId[$componentVariantId] ?? 0)) * max(0, $qty);

                            ProductPackageItem::query()->create([
                                'package_product_id' => (int) $product->id,
                                'component_product_variant_id' => $componentVariantId,
                                'quantity' => $qty,
                                'sort_order' => (int) $index,
                            ]);
                        }

                        if ($keptVariantIds !== []) {
                            $product->variants()->whereIn('id', $keptVariantIds)->update([
                                'hpp' => (int) $packageHpp,
                            ]);
                        }
                    }

                    if ($packageType === 'complex') {
                        $componentProductIds = array_map(fn (array $row) => (int) ($row['component_product_id'] ?? 0), $complexPackageItems);
                        $componentProductIds = array_filter($componentProductIds, fn (int $id) => $id > 0);

                        if (count($componentProductIds) !== count(array_unique($componentProductIds))) {
                            throw ValidationException::withMessages([
                                'complexPackageItems' => 'Isi paket tidak boleh duplikat untuk produk yang sama.',
                            ]);
                        }

                        foreach (array_values($complexPackageItems) as $index => $row) {
                            ProductComplexPackageItem::query()->create([
                                'package_product_id' => (int) $product->id,
                                'component_product_id' => (int) $row['component_product_id'],
                                'quantity' => (int) $row['quantity'],
                                'is_splitable' => (bool) ($row['is_splitable'] ?? false),
                                'sort_order' => (int) $index,
                            ]);
                        }

                        if ($keptVariantIds !== []) {
                            $product->variants()->whereIn('id', $keptVariantIds)->update([
                                'hpp' => 0,
                            ]);
                        }
                    }
                } else {
                    ProductComplexPackageItem::query()
                        ->where('package_product_id', (int) $product->id)
                        ->delete();

                    foreach ($variantKeyToId as $variantId) {
                        $hpp = 0.0;

                        $rows = ProductVariantRecipe::query()
                            ->where('product_variant_id', $variantId)
                            ->get(['ingredient_id', 'quantity']);

                        foreach ($rows as $row) {
                            $cost = (float) ($ingredientCosts[(int) $row->ingredient_id] ?? 0);
                            $hpp += $cost * (float) $row->quantity;
                        }

                        $product->variants()->whereKey($variantId)->update([
                            'hpp' => (int) round($hpp),
                        ]);
                    }
                }
            });
        } catch (ValidationException $e) {
            if ($newImagePath !== null) {
                Storage::disk('public')->delete($newImagePath);
            }

            throw $e;
        }

        if ($newImagePath !== null && $this->existingImage !== '' && ! str_starts_with($this->existingImage, '/') && ! str_starts_with($this->existingImage, 'http')) {
            Storage::disk('public')->delete($this->existingImage);
        }

        $this->redirectRoute('products.index', navigate: true);
    }

    private function makeEmptyVariant(): array
    {
        return [
            'id' => null,
            'key' => (string) Str::uuid(),
            'name' => '',
            'price' => '',
            'percent' => null,
        ];
    }

    private function makeEmptyRecipe(): array
    {
        return [
            'id' => null,
            'key' => (string) Str::uuid(),
            'ingredient_id' => null,
            'quantity' => '',
        ];
    }

    private function makeEmptyPackageItem(): array
    {
        return [
            'id' => null,
            'key' => (string) Str::uuid(),
            'component_variant_id' => null,
            'quantity' => 1,
        ];
    }

    private function makeEmptyComplexPackageItem(): array
    {
        return [
            'id' => null,
            'key' => (string) Str::uuid(),
            'component_product_id' => null,
            'quantity' => 1,
            'is_splitable' => false,
        ];
    }

    private function normalizeForValidation(): void
    {
        foreach ($this->variants as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('percent', $row) && $row['percent'] === '') {
                $this->variants[$index]['percent'] = null;
            }

            if (array_key_exists('id', $row) && $row['id'] === '') {
                $this->variants[$index]['id'] = null;
            }

            if (! array_key_exists('key', $row) || (string) $row['key'] === '') {
                $this->variants[$index]['key'] = (string) Str::uuid();
            }
        }

        foreach ($this->variantRecipes as $variantKey => $recipes) {
            if (! is_array($recipes)) {
                continue;
            }

            foreach ($recipes as $recipeIndex => $recipe) {
                if (! is_array($recipe)) {
                    continue;
                }

                if (array_key_exists('id', $recipe) && $recipe['id'] === '') {
                    $this->variantRecipes[$variantKey][$recipeIndex]['id'] = null;
                }

                if (! array_key_exists('key', $recipe) || (string) $recipe['key'] === '') {
                    $this->variantRecipes[$variantKey][$recipeIndex]['key'] = (string) Str::uuid();
                }

                if (array_key_exists('ingredient_id', $recipe) && $recipe['ingredient_id'] === '') {
                    $this->variantRecipes[$variantKey][$recipeIndex]['ingredient_id'] = null;
                }
            }
        }

        foreach ($this->packageItems as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('id', $row) && $row['id'] === '') {
                $this->packageItems[$index]['id'] = null;
            }

            if (array_key_exists('component_variant_id', $row) && $row['component_variant_id'] === '') {
                $this->packageItems[$index]['component_variant_id'] = null;
            }

            if (! array_key_exists('key', $row) || (string) $row['key'] === '') {
                $this->packageItems[$index]['key'] = (string) Str::uuid();
            }
        }

        foreach ($this->complexPackageItems as $index => $row) {
            if (! is_array($row)) {
                continue;
            }

            if (array_key_exists('id', $row) && $row['id'] === '') {
                $this->complexPackageItems[$index]['id'] = null;
            }

            if (array_key_exists('component_product_id', $row) && $row['component_product_id'] === '') {
                $this->complexPackageItems[$index]['component_product_id'] = null;
            }

            if (! array_key_exists('key', $row) || (string) $row['key'] === '') {
                $this->complexPackageItems[$index]['key'] = (string) Str::uuid();
            }

            if (! array_key_exists('is_splitable', $row)) {
                $this->complexPackageItems[$index]['is_splitable'] = false;
            }
        }
    }

    public function render(): View
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get(['id', 'name']);

        $printerSources = $this->kitchenPrinterSourcesQuery()
            ->orderBy('name')
            ->get(['id', 'name', 'type']);

        $ingredients = Ingredient::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name', 'unit', 'cost_price']);

        $componentVariants = ProductVariant::query()
            ->whereHas('product', fn ($q) => $q->where('is_package', false))
            ->with(['product:id,name'])
            ->orderBy('product_id')
            ->orderBy('name')
            ->get(['id', 'product_id', 'name', 'hpp']);

        $componentProducts = Product::query()
            ->where('is_package', false)
            ->orderBy('name')
            ->get(['id', 'name']);

        $ingredientCosts = $ingredients->pluck('cost_price', 'id')->map(fn ($v) => (float) $v)->all();
        $ingredientUnits = $ingredients->pluck('unit', 'id')->map(fn ($v) => (string) $v)->all();

        $hppByVariantKey = [];

        foreach ($this->variants as $variant) {
            $variantKey = (string) ($variant['key'] ?? '');
            $recipes = (array) ($this->variantRecipes[$variantKey] ?? []);
            $hpp = 0.0;

            foreach ($recipes as $row) {
                $ingredientId = (int) ($row['ingredient_id'] ?? 0);
                $qty = (float) (QuantityParser::parse($row['quantity'] ?? null) ?? 0);
                $hpp += (float) ($ingredientCosts[$ingredientId] ?? 0) * $qty;
            }

            $hppByVariantKey[$variantKey] = (int) round($hpp);
        }

        if ($this->isPackage && $this->packageType === 'simple') {
            $componentHppByVariantId = $componentVariants
                ->keyBy('id')
                ->map(fn (ProductVariant $v) => (int) ($v->hpp ?? 0))
                ->all();

            $packageHpp = 0;
            foreach ($this->packageItems as $row) {
                $componentVariantId = (int) ($row['component_variant_id'] ?? 0);
                $qty = (int) ($row['quantity'] ?? 0);
                if ($componentVariantId <= 0 || $qty <= 0) {
                    continue;
                }
                $packageHpp += max(0, (int) ($componentHppByVariantId[$componentVariantId] ?? 0)) * $qty;
            }

            foreach ($this->variants as $variant) {
                $variantKey = (string) ($variant['key'] ?? '');
                $hppByVariantKey[$variantKey] = (int) $packageHpp;
            }
        }

        return view('livewire.products.product-form-page', [
            'categories' => $categories,
            'printerSources' => $printerSources,
            'ingredients' => $ingredients,
            'componentVariants' => $componentVariants,
            'componentProducts' => $componentProducts,
            'hppByVariantKey' => $hppByVariantKey,
            'ingredientUnits' => $ingredientUnits,
            'ingredientCosts' => $ingredientCosts,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
