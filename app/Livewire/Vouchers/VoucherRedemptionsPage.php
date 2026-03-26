<?php

namespace App\Livewire\Vouchers;

use App\Models\VoucherCampaign;
use App\Models\VoucherRedemption;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class VoucherRedemptionsPage extends Component
{
    use WithPagination;

    public string $title = 'Riwayat Pemakaian Voucher';

    public ?int $campaignId = null;

    public ?string $from = null;

    public ?string $to = null;

    public string $rangePreset = '30d';

    public string $codeSearch = '';

    public string $customerSearch = '';

    public function mount(): void
    {
        $this->setRange('30d');
    }

    public function updatedCampaignId(): void
    {
        $this->resetPage();
    }

    public function updatedFrom(): void
    {
        $this->resetPage();
    }

    public function updatedTo(): void
    {
        $this->resetPage();
    }

    public function updatedCodeSearch(): void
    {
        $this->resetPage();
    }

    public function updatedCustomerSearch(): void
    {
        $this->resetPage();
    }

    public function setTransactionsRange(?string $from, ?string $to): void
    {
        if (! $from || ! $to) {
            return;
        }

        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->from = $from;
        $this->to = $to;
        $this->rangePreset = 'custom';
        $this->resetPage();
    }

    public function setRange(string $preset): void
    {
        $today = CarbonImmutable::now();

        if ($preset === 'today') {
            $from = $today;
            $to = $today;
        } elseif ($preset === '7d') {
            $from = $today->subDays(6);
            $to = $today;
        } elseif ($preset === '30d') {
            $from = $today->subDays(29);
            $to = $today;
        } elseif ($preset === 'custom') {
            return;
        } else {
            return;
        }

        $this->from = $from->format('Y-m-d');
        $this->to = $to->format('Y-m-d');
        $this->rangePreset = $preset;
        $this->resetPage();
    }

    public function exportCsv()
    {
        $filename = 'riwayat-pemakaian-voucher-'.now()->format('Ymd_His').'.csv';

        $query = $this->baseQuery()->orderByDesc('redeemed_at');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Waktu', 'Program', 'Kode', 'Diskon', 'Pelanggan', 'Tamu', 'Kode Transaksi', 'Total Transaksi']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        (string) $row->redeemed_at,
                        (string) ($row->campaign?->name ?? ''),
                        (string) ($row->code?->code ?? ''),
                        (int) $row->discount_amount,
                        (string) ($row->member?->name ?? ''),
                        (string) ($row->guest_identifier ?? ''),
                        (string) ($row->transaction?->code ?? ''),
                        (int) ($row->transaction?->total ?? 0),
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function baseQuery(): Builder
    {
        return VoucherRedemption::query()
            ->with(['campaign', 'code', 'transaction', 'member'])
            ->when($this->campaignId, fn (Builder $q) => $q->where('voucher_campaign_id', (int) $this->campaignId))
            ->when($this->from, fn (Builder $q) => $q->whereDate('redeemed_at', '>=', (string) $this->from))
            ->when($this->to, fn (Builder $q) => $q->whereDate('redeemed_at', '<=', (string) $this->to))
            ->when(trim($this->codeSearch) !== '', function (Builder $q) {
                $term = '%'.trim($this->codeSearch).'%';
                $q->whereHas('code', fn (Builder $c) => $c->where('code', 'like', $term));
            })
            ->when(trim($this->customerSearch) !== '', function (Builder $q) {
                $term = '%'.trim($this->customerSearch).'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('guest_identifier', 'like', $term)
                        ->orWhereHas('member', fn (Builder $m) => $m->where('name', 'like', $term)->orWhere('phone', 'like', $term));
                });
            });
    }

    public function render(): View
    {
        $campaigns = VoucherCampaign::query()->orderBy('name')->get(['id', 'name']);
        $summary = (clone $this->baseQuery())
            ->selectRaw('COUNT(*) as cnt')
            ->selectRaw('COALESCE(SUM(discount_amount), 0) as sum_discount')
            ->first();

        $rows = $this->baseQuery()->orderByDesc('redeemed_at')->paginate(25);

        return view('livewire.vouchers.voucher-redemptions-page', [
            'campaigns' => $campaigns,
            'rows' => $rows,
            'summaryCount' => (int) ($summary->cnt ?? 0),
            'summaryDiscount' => (int) ($summary->sum_discount ?? 0),
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
