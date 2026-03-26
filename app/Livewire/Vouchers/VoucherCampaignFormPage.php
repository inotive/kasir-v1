<?php

namespace App\Livewire\Vouchers;

use App\Models\Category;
use App\Models\VoucherCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\Rule;
use Livewire\Component;

class VoucherCampaignFormPage extends Component
{
    public string $title = 'Program Voucher';

    public ?int $campaignId = null;

    public bool $is_active = true;

    public string $name = '';

    public ?string $description = null;

    public string $discount_type = 'percent';

    public int $discount_value = 10;

    public ?int $max_discount_amount = null;

    public ?int $min_eligible_subtotal = null;

    public ?string $starts_at = null;

    public ?string $ends_at = null;

    public ?int $usage_limit_total = null;

    public ?int $usage_limit_per_user = null;

    public bool $is_member_only = false;

    public ?string $terms = null;

    public array $eligible_category_ids = [];

    public function mount(?VoucherCampaign $campaign = null): void
    {
        if ($campaign) {
            $this->campaignId = (int) $campaign->id;
            $this->title = 'Ubah Program - '.$campaign->name;
            $this->is_active = (bool) $campaign->is_active;
            $this->name = (string) $campaign->name;
            $this->description = $campaign->description;
            $this->discount_type = (string) $campaign->discount_type;
            $this->discount_value = (int) $campaign->discount_value;
            $this->max_discount_amount = $campaign->max_discount_amount === null ? null : (int) $campaign->max_discount_amount;
            $this->min_eligible_subtotal = $campaign->min_eligible_subtotal === null ? null : (int) $campaign->min_eligible_subtotal;
            $this->starts_at = $campaign->starts_at ? $campaign->starts_at->format('Y-m-d') : null;
            $this->ends_at = $campaign->ends_at ? $campaign->ends_at->format('Y-m-d') : null;
            $this->usage_limit_total = $campaign->usage_limit_total === null ? null : (int) $campaign->usage_limit_total;
            $this->usage_limit_per_user = $campaign->usage_limit_per_user === null ? null : (int) $campaign->usage_limit_per_user;
            $this->is_member_only = (bool) $campaign->is_member_only;
            $this->terms = $campaign->terms;
            $this->eligible_category_ids = $campaign->eligibleCategories()->pluck('categories.id')->map(fn ($v) => (int) $v)->all();
        } else {
            $this->title = 'Buat Program Voucher';
        }
    }

    public function save(): void
    {
        $this->resetValidation();

        $data = $this->validate([
            'is_active' => ['boolean'],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:255'],
            'discount_type' => ['required', Rule::in(['percent', 'fixed_amount'])],
            'discount_value' => ['required', 'integer', 'min:1'],
            'max_discount_amount' => ['nullable', 'integer', 'min:0'],
            'min_eligible_subtotal' => ['nullable', 'integer', 'min:0'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'usage_limit_total' => ['nullable', 'integer', 'min:0'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:0'],
            'is_member_only' => ['boolean'],
            'terms' => ['nullable', 'string'],
            'eligible_category_ids' => ['array'],
            'eligible_category_ids.*' => ['integer', 'exists:categories,id'],
        ]);

        if ($data['discount_type'] === 'percent') {
            $data['discount_value'] = max(1, min(100, (int) $data['discount_value']));
        }

        $campaign = $this->campaignId
            ? VoucherCampaign::query()->findOrFail($this->campaignId)
            : new VoucherCampaign;

        $campaign->fill([
            'is_active' => (bool) $data['is_active'],
            'name' => (string) $data['name'],
            'description' => $data['description'] !== '' ? $data['description'] : null,
            'discount_type' => (string) $data['discount_type'],
            'discount_value' => (int) $data['discount_value'],
            'max_discount_amount' => $data['max_discount_amount'],
            'min_eligible_subtotal' => $data['min_eligible_subtotal'],
            'starts_at' => $data['starts_at'] ? $data['starts_at'].' 00:00:00' : null,
            'ends_at' => $data['ends_at'] ? $data['ends_at'].' 23:59:59' : null,
            'usage_limit_total' => $data['usage_limit_total'],
            'usage_limit_per_user' => $data['usage_limit_per_user'],
            'is_member_only' => (bool) $data['is_member_only'],
            'terms' => $data['terms'],
            'created_by_user_id' => $campaign->exists ? $campaign->created_by_user_id : auth()->id(),
        ]);

        $campaign->save();
        $campaign->eligibleCategories()->sync(array_map('intval', $data['eligible_category_ids'] ?? []));

        $this->campaignId = (int) $campaign->id;

        $this->dispatch('notify', type: 'success', message: 'Program voucher tersimpan.');
    }

    public function toggleEligibleCategory(int $categoryId): void
    {
        if ($categoryId <= 0) {
            return;
        }

        if (! Category::query()->whereKey($categoryId)->exists()) {
            return;
        }

        $current = array_values(array_map('intval', $this->eligible_category_ids ?? []));
        $exists = in_array($categoryId, $current, true);

        if ($exists) {
            $current = array_values(array_filter($current, fn (int $id) => $id !== $categoryId));
        } else {
            $current[] = $categoryId;
        }

        $current = array_values(array_unique($current));
        sort($current);

        $this->eligible_category_ids = $current;
        $this->resetValidation('eligible_category_ids');
    }

    public function clearEligibleCategories(): void
    {
        $this->eligible_category_ids = [];
        $this->resetValidation('eligible_category_ids');
    }

    public function render(): View
    {
        $categories = Category::query()->orderBy('name')->get(['id', 'name']);

        return view('livewire.vouchers.voucher-campaign-form-page', [
            'categories' => $categories,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
