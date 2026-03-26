<?php

namespace App\Livewire\Vouchers;

use App\Models\VoucherCampaign;
use App\Models\VoucherCode;
use App\Services\Vouchers\VoucherCodeGenerator;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class VoucherCodesPage extends Component
{
    use WithPagination;

    public string $title = 'Kode Voucher';

    public int $campaignId;

    public string $search = '';

    public string $generateMode = 'alphanumeric';

    public int $generateCount = 20;

    public int $generateLength = 10;

    public string $generatePattern = 'PROMO-{YYYY}{MM}-{RAND:6}';

    public string $customCodes = '';

    public function mount(VoucherCampaign $campaign): void
    {
        $this->campaignId = (int) $campaign->id;
        $this->title = 'Kode Voucher - '.$campaign->name;
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $codeId): void
    {
        $code = VoucherCode::query()->where('voucher_campaign_id', $this->campaignId)->findOrFail($codeId);
        $code->is_active = ! (bool) $code->is_active;
        $code->save();
    }

    public function generateCodes(VoucherCodeGenerator $generator): void
    {
        $this->resetValidation();

        $data = $this->validate([
            'generateMode' => ['required', 'in:alphanumeric,pattern,custom'],
            'generateCount' => ['required', 'integer', 'min:1', 'max:500'],
            'generateLength' => ['required_if:generateMode,alphanumeric', 'integer', 'min:4', 'max:30'],
            'generatePattern' => ['required_if:generateMode,pattern', 'string', 'max:120'],
            'customCodes' => ['required_if:generateMode,custom', 'string'],
        ]);

        $campaignId = (int) $this->campaignId;
        $created = 0;

        if ($data['generateMode'] === 'custom') {
            $lines = preg_split('/\r\n|\r|\n/', (string) $data['customCodes']) ?: [];
            $codes = [];
            foreach ($lines as $line) {
                $norm = $generator->normalizeCustom((string) $line);
                if ($norm !== '') {
                    $codes[$norm] = true;
                }
            }
            $created = $this->createCodes($campaignId, array_keys($codes));
        } elseif ($data['generateMode'] === 'pattern') {
            $codes = $generator->generateFromPattern((string) $data['generatePattern'], (int) $data['generateCount']);
            $created = $this->createCodes($campaignId, $codes);
        } else {
            $codes = $generator->generateAlphanumeric((int) $data['generateCount'], (int) $data['generateLength']);
            $created = $this->createCodes($campaignId, $codes);
        }

        $this->dispatch('notify', type: 'success', message: 'Kode berhasil dibuat: '.number_format($created, 0, ',', '.'));
        $this->resetPage();
    }

    public function exportCodesCsv()
    {
        $campaign = VoucherCampaign::query()->findOrFail($this->campaignId);
        $filename = 'kode-voucher-'.$campaign->id.'-'.now()->format('Ymd_His').'.csv';

        $query = VoucherCode::query()
            ->where('voucher_campaign_id', $this->campaignId)
            ->orderByDesc('created_at');

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Kode', 'Aktif', 'Jumlah Dipakai', 'Dibuat Pada']);
            $query->chunk(500, function ($rows) use ($out) {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        (string) $row->code,
                        (int) $row->is_active,
                        (int) $row->times_redeemed,
                        (string) $row->created_at,
                    ]);
                }
            });
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv']);
    }

    protected function createCodes(int $campaignId, array $codes): int
    {
        $count = 0;
        foreach ($codes as $code) {
            $code = strtoupper(trim((string) $code));
            if ($code === '') {
                continue;
            }
            try {
                VoucherCode::query()->create([
                    'voucher_campaign_id' => $campaignId,
                    'code' => $code,
                    'is_active' => true,
                ]);
                $count++;
            } catch (\Throwable $e) {
            }
        }

        return $count;
    }

    public function render(): View
    {
        $campaign = VoucherCampaign::query()->withCount('codes')->findOrFail($this->campaignId);

        $codes = VoucherCode::query()
            ->where('voucher_campaign_id', $this->campaignId)
            ->when(trim($this->search) !== '', function (Builder $q) {
                $term = '%'.trim($this->search).'%';
                $q->where('code', 'like', $term);
            })
            ->orderByDesc('created_at')
            ->paginate(20);

        return view('livewire.vouchers.voucher-codes-page', [
            'campaign' => $campaign,
            'codes' => $codes,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
