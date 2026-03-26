<?php

namespace App\Livewire\Vouchers;

use App\Models\Setting;
use App\Models\VoucherCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class VoucherCampaignsPage extends Component
{
    use WithPagination;

    public string $title = 'Voucher';

    public string $search = '';

    public string $status = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function render(): View
    {
        $setting = Setting::current();
        $daysBeforeExpiry = max(0, (int) ($setting->voucher_alert_days_before_expiry ?? 7));
        $quotaThreshold = max(0, (int) ($setting->voucher_alert_quota_threshold ?? 10));

        $query = VoucherCampaign::query()
            ->withCount(['codes', 'redemptions'])
            ->withSum('codes', 'times_redeemed')
            ->withSum('redemptions', 'discount_amount')
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('name', 'like', $term)
                        ->orWhere('description', 'like', $term);
                });
            })
            ->when($this->status !== '', function (Builder $q) {
                if ($this->status === 'active') {
                    $q->where('is_active', true);
                } elseif ($this->status === 'inactive') {
                    $q->where('is_active', false);
                } elseif ($this->status === 'running') {
                    $q->where('is_active', true)
                        ->where(function (Builder $w) {
                            $w->whereNull('starts_at')->orWhere('starts_at', '<=', now());
                        })
                        ->where(function (Builder $w) {
                            $w->whereNull('ends_at')->orWhere('ends_at', '>=', now());
                        });
                }
            })
            ->orderByDesc('created_at');

        $campaigns = $query->paginate(15);

        return view('livewire.vouchers.voucher-campaigns-page', [
            'campaigns' => $campaigns,
            'daysBeforeExpiry' => $daysBeforeExpiry,
            'quotaThreshold' => $quotaThreshold,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
