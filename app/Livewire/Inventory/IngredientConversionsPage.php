<?php

namespace App\Livewire\Inventory;

use App\Models\Ingredient;
use App\Models\IngredientUnitConversion;
use App\Support\Number\QuantityFormatter;
use App\Support\Number\QuantityParser;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class IngredientConversionsPage extends Component
{
    public string $title = 'Konversi Unit';

    public int $ingredientId;

    public string $ingredientName = '';

    public string $baseUnit = '';

    public bool $createConversionModalOpen = false;

    public string $unit = '';

    public ?string $factorToBase = null;

    public ?int $editingConversionId = null;

    public string $editingUnit = '';

    public ?string $editingFactorToBase = null;

    public function mount(Ingredient $ingredient): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $this->ingredientId = (int) $ingredient->id;
        $this->ingredientName = (string) $ingredient->name;
        $this->baseUnit = (string) $ingredient->unit;
        $this->title = 'Konversi Unit - '.$this->ingredientName;
    }

    public function openCreateConversionModal(): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $this->reset(['unit', 'factorToBase']);
        $this->resetValidation();
        $this->createConversionModalOpen = true;
    }

    public function closeCreateConversionModal(): void
    {
        $this->createConversionModalOpen = false;
        $this->resetValidation();
    }

    public function createConversion(): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $validated = $this->validate([
            'unit' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ingredient_unit_conversions', 'unit')->where(fn ($q) => $q->where('ingredient_id', $this->ingredientId)),
            ],
            'factorToBase' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0.000001) {
                    $fail('Factor tidak valid.');
                }
            }],
        ]);

        IngredientUnitConversion::query()->create([
            'ingredient_id' => $this->ingredientId,
            'unit' => $validated['unit'],
            'factor_to_base' => (float) (QuantityParser::parse($validated['factorToBase'] ?? null) ?? 0),
        ]);

        $this->reset(['unit', 'factorToBase']);
        $this->createConversionModalOpen = false;
        $this->dispatch('toast', type: 'success', message: 'Konversi berhasil ditambahkan.');
    }

    public function startEdit(int $conversionId): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        $conversion = IngredientUnitConversion::query()
            ->where('ingredient_id', $this->ingredientId)
            ->findOrFail($conversionId);

        $this->editingConversionId = (int) $conversion->id;
        $this->editingUnit = (string) $conversion->unit;
        $this->editingFactorToBase = QuantityFormatter::format((float) $conversion->factor_to_base, 6);
        $this->resetValidation();
    }

    public function cancelEdit(): void
    {
        $this->editingConversionId = null;
        $this->reset(['editingUnit', 'editingFactorToBase']);
        $this->resetValidation();
    }

    public function updateConversion(): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        if (! $this->editingConversionId) {
            return;
        }

        $validated = $this->validate([
            'editingUnit' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ingredient_unit_conversions', 'unit')
                    ->where(fn ($q) => $q->where('ingredient_id', $this->ingredientId))
                    ->ignore($this->editingConversionId),
            ],
            'editingFactorToBase' => ['required', function (string $attribute, $value, $fail): void {
                $parsed = QuantityParser::parse($value);
                if ($parsed === null || $parsed < 0.000001) {
                    $fail('Factor tidak valid.');
                }
            }],
        ]);

        IngredientUnitConversion::query()
            ->whereKey($this->editingConversionId)
            ->where('ingredient_id', $this->ingredientId)
            ->update([
                'unit' => $validated['editingUnit'],
                'factor_to_base' => (float) (QuantityParser::parse($validated['editingFactorToBase'] ?? null) ?? 0),
            ]);

        $this->cancelEdit();
    }

    public function deleteConversion(int $conversionId): void
    {
        $this->authorizeAny(['inventory.ingredients.manage', 'inventory.manage']);

        IngredientUnitConversion::query()
            ->where('ingredient_id', $this->ingredientId)
            ->whereKey($conversionId)
            ->delete();

        if ($this->editingConversionId === $conversionId) {
            $this->cancelEdit();
        }
    }

    public function render(): View
    {
        $ingredient = Ingredient::query()->findOrFail($this->ingredientId);

        $conversions = IngredientUnitConversion::query()
            ->where('ingredient_id', $this->ingredientId)
            ->orderBy('unit')
            ->get(['id', 'unit', 'factor_to_base']);

        return view('livewire.inventory.ingredient-conversions-page', [
            'ingredient' => $ingredient,
            'conversions' => $conversions,
            'canManage' => true,
        ])->layout('layouts.app', ['title' => $this->title]);
    }

    private function authorizeAny(array $permissions): void
    {
        $user = auth()->user();
        if (! $user || ! method_exists($user, 'can')) {
            abort(403);
        }

        foreach ($permissions as $permission) {
            $permission = (string) $permission;
            if ($permission !== '' && $user->can($permission)) {
                return;
            }
        }

        abort(403);
    }
}
