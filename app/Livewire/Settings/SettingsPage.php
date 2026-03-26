<?php

namespace App\Livewire\Settings;

use App\Models\MonthlyRevenueTarget;
use App\Models\PrinterSource;
use App\Models\Setting;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class SettingsPage extends Component
{
    use WithFileUploads;

    public string $title = 'Pengaturan';

    public string $activeSection = 'store';

    public ?string $store_name = null;

    public ?string $phone = null;

    public ?string $address = null;

    public bool $payment_gateway_enabled = true;

    public float $tax_rate = 0;

    public $store_logo_upload = null;

    public ?string $store_logo_path = null;

    public int $rounding_base = 100;

    public bool $discount_applies_before_tax = true;

    public string $pos_default_customer_name = 'Walk-in';

    public string $pos_default_payment_method = 'cash';

    public bool $corrections_void_pending_requires_approval = false;

    public bool $corrections_refund_requires_approval_for_cash = true;

    public int $corrections_refund_quick_max_amount = 20000;

    public int $corrections_refund_quick_max_count_per_day = 2;

    public int $corrections_void_quick_max_count_per_day = 3;

    public int $corrections_void_quick_window_minutes = 5;

    public float $point_earning_rate = 0;

    public float $point_redemption_value = 0;

    public int $min_redemption_points = 0;

    public string $printerName = '';

    public string $printerType = 'kasir';

    public bool $cashier_receipt_print_logo = true;

    public ?int $editingPrinterSourceId = null;

    public string $editingPrinterName = '';

    public string $editingPrinterType = '';

    public int $monthlyTargetYear = 0;

    public int $monthlyTargetMonth = 0;

    public int $monthlyTargetAmount = 0;

    public array $monthlyTargets = [];

    public function mount(): void
    {
        $this->authorize('settings.view');

        $setting = Setting::current();

        $this->store_name = $setting->store_name;
        $this->phone = $setting->phone;
        $this->address = $setting->address;
        $this->payment_gateway_enabled = (bool) $setting->payment_gateway_enabled;
        $this->tax_rate = (float) $setting->tax_rate;
        $this->store_logo_path = $setting->store_logo;
        $this->rounding_base = max(0, (int) $setting->rounding_base);
        $this->discount_applies_before_tax = (bool) ($setting->discount_applies_before_tax ?? true);
        $this->pos_default_customer_name = (string) ($setting->pos_default_customer_name ?? 'Walk-in');
        $this->pos_default_payment_method = (string) ($setting->pos_default_payment_method ?? 'cash');
        $this->cashier_receipt_print_logo = (bool) ($setting->cashier_receipt_print_logo ?? true);
        $this->corrections_void_pending_requires_approval = (bool) ($setting->corrections_void_pending_requires_approval ?? false);
        $this->corrections_refund_requires_approval_for_cash = (bool) ($setting->corrections_refund_requires_approval_for_cash ?? true);
        $this->corrections_refund_quick_max_amount = max(0, (int) ($setting->corrections_refund_quick_max_amount ?? 20000));
        $this->corrections_refund_quick_max_count_per_day = max(0, (int) ($setting->corrections_refund_quick_max_count_per_day ?? 2));
        $this->corrections_void_quick_max_count_per_day = max(0, (int) ($setting->corrections_void_quick_max_count_per_day ?? 3));
        $this->corrections_void_quick_window_minutes = max(0, (int) ($setting->corrections_void_quick_window_minutes ?? 5));
        $this->point_earning_rate = (float) ($setting->point_earning_rate ?? 0);
        $this->point_redemption_value = (float) ($setting->point_redemption_value ?? 0);
        $this->min_redemption_points = (int) ($setting->min_redemption_points ?? 0);

        $now = now();
        $this->monthlyTargetYear = (int) $now->year;
        $this->monthlyTargetMonth = (int) $now->month;
        $this->monthlyTargetAmount = (int) (MonthlyRevenueTarget::query()
            ->where('year', $this->monthlyTargetYear)
            ->where('month', $this->monthlyTargetMonth)
            ->value('amount') ?? 0);
        $this->monthlyTargets = $this->loadMonthlyTargets();

        $section = request()->query('section');
        if (is_string($section) && $section !== '' && in_array($section, ['store', 'printers', 'system', 'points', 'targets'], true)) {
            if ($this->canViewSection($section)) {
                $this->activeSection = $section;
            }
        }

        if (! $this->canViewSection($this->activeSection)) {
            $this->activeSection = $this->firstAllowedSection();
        }
    }

    public function setSection(string $section): void
    {
        $allowed = ['store', 'printers', 'system', 'points', 'targets'];
        if (! in_array($section, $allowed, true)) {
            return;
        }

        if (! $this->canViewSection($section)) {
            return;
        }

        $this->activeSection = $section;
        $this->resetValidation();

        if ($section === 'printers') {
            $this->dispatch('printer-sources-updated', sources: $this->printerSourcesForJs());
        }

        if ($section === 'targets') {
            $this->monthlyTargets = $this->loadMonthlyTargets();
        }
    }

    private function sectionPermission(string $section, string $ability): string
    {
        return match ($section) {
            'store' => $ability === 'edit' ? 'settings.store.edit' : 'settings.store.view',
            'printers' => $ability === 'edit' ? 'settings.printers.edit' : 'settings.printers.view',
            'system' => $ability === 'edit' ? 'settings.system.edit' : 'settings.system.view',
            'points' => $ability === 'edit' ? 'settings.points.edit' : 'settings.points.view',
            'targets' => $ability === 'edit' ? 'settings.targets.edit' : 'settings.targets.view',
            default => 'settings.view',
        };
    }

    private function canEditAllSettings(): bool
    {
        return auth()->user()?->can('settings.edit') ?? false;
    }

    public function canViewSection(string $section): bool
    {
        if ($this->canEditAllSettings()) {
            return true;
        }

        $view = $this->sectionPermission($section, 'view');
        $edit = $this->sectionPermission($section, 'edit');

        return (auth()->user()?->can($view) ?? false) || (auth()->user()?->can($edit) ?? false);
    }

    private function firstAllowedSection(): string
    {
        foreach (['store', 'printers', 'system', 'points', 'targets'] as $section) {
            if ($this->canViewSection($section)) {
                return $section;
            }
        }

        return 'store';
    }

    private function authorizeSectionView(string $section): void
    {
        if ($this->canEditAllSettings()) {
            return;
        }

        $view = $this->sectionPermission($section, 'view');
        $edit = $this->sectionPermission($section, 'edit');

        if ((auth()->user()?->can($view) ?? false) || (auth()->user()?->can($edit) ?? false)) {
            return;
        }

        abort(403);
    }

    private function authorizeSectionEdit(string $section): void
    {
        if ($this->canEditAllSettings()) {
            return;
        }

        $this->authorize($this->sectionPermission($section, 'edit'));
    }

    private function loadMonthlyTargets(): array
    {
        return MonthlyRevenueTarget::query()
            ->orderByDesc('year')
            ->orderByDesc('month')
            ->limit(24)
            ->get(['year', 'month', 'amount'])
            ->map(fn (MonthlyRevenueTarget $t) => [
                'year' => (int) $t->year,
                'month' => (int) $t->month,
                'amount' => (int) $t->amount,
            ])
            ->all();
    }

    public function selectMonthlyTarget(int $year, int $month): void
    {
        $this->authorizeSectionView('targets');

        $amount = MonthlyRevenueTarget::query()
            ->where('year', $year)
            ->where('month', $month)
            ->value('amount');

        $this->monthlyTargetYear = $year;
        $this->monthlyTargetMonth = $month;
        $this->monthlyTargetAmount = (int) ($amount ?? 0);
        $this->resetValidation();
    }

    public function saveMonthlyTarget(): void
    {
        $this->authorizeSectionEdit('targets');

        $validated = $this->validate([
            'monthlyTargetYear' => ['required', 'integer', 'min:2000', 'max:2100'],
            'monthlyTargetMonth' => ['required', 'integer', 'min:1', 'max:12'],
            'monthlyTargetAmount' => ['required', 'integer', 'min:0', 'max:100000000000'],
        ]);

        MonthlyRevenueTarget::query()->updateOrCreate(
            [
                'year' => (int) $validated['monthlyTargetYear'],
                'month' => (int) $validated['monthlyTargetMonth'],
            ],
            [
                'amount' => (int) $validated['monthlyTargetAmount'],
            ],
        );

        $this->monthlyTargets = $this->loadMonthlyTargets();
        $this->dispatch('toast', type: 'success', message: 'Target bulanan berhasil disimpan.');
    }

    public function deleteMonthlyTarget(int $year, int $month): void
    {
        $this->authorizeSectionEdit('targets');

        MonthlyRevenueTarget::query()
            ->where('year', $year)
            ->where('month', $month)
            ->delete();

        $now = now();
        if ($this->monthlyTargetYear === (int) $now->year && $this->monthlyTargetMonth === (int) $now->month) {
            $this->monthlyTargetAmount = 0;
        }

        $this->monthlyTargets = $this->loadMonthlyTargets();
        $this->dispatch('toast', type: 'success', message: 'Target bulanan berhasil dihapus.');
    }

    public function saveStoreSettings(): void
    {
        $this->authorizeSectionEdit('store');

        $validated = $this->validate([
            'store_name' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string', 'max:2000'],
            'payment_gateway_enabled' => ['boolean'],
            'tax_rate' => ['numeric', 'min:0', 'max:100'],
            'store_logo_upload' => ['nullable', 'image', 'max:2048'],
        ]);

        $setting = Setting::current();

        if ($this->store_logo_upload) {
            $path = $this->store_logo_upload->store('store', 'public');
            $old = $setting->store_logo;

            $setting->store_logo = $path;
            $this->store_logo_path = $path;

            if ($old && $old !== $path) {
                Storage::disk('public')->delete($old);
            }
        }

        $setting->store_name = $validated['store_name'] !== null && trim($validated['store_name']) !== '' ? trim($validated['store_name']) : null;
        $setting->phone = $validated['phone'] !== null && trim($validated['phone']) !== '' ? trim($validated['phone']) : null;
        $setting->address = $validated['address'] !== null && trim($validated['address']) !== '' ? trim($validated['address']) : null;
        $setting->payment_gateway_enabled = (bool) $validated['payment_gateway_enabled'];
        $setting->tax_rate = (float) $validated['tax_rate'];
        $setting->save();

        $this->store_logo_upload = null;

        $this->dispatch('toast', type: 'success', message: 'Pengaturan toko berhasil disimpan.');
    }

    public function saveSystemSettings(): void
    {
        $this->authorizeSectionEdit('system');

        $validated = $this->validate([
            'rounding_base' => ['required', 'integer', 'min:0', 'max:1000000'],
            'discount_applies_before_tax' => ['boolean'],
            'pos_default_customer_name' => ['required', 'string', 'max:255'],
            'pos_default_payment_method' => ['required', 'string', 'max:50'],
            'corrections_void_pending_requires_approval' => ['boolean'],
            'corrections_refund_requires_approval_for_cash' => ['boolean'],
            'corrections_refund_quick_max_amount' => ['required', 'integer', 'min:0', 'max:4294967295'],
            'corrections_refund_quick_max_count_per_day' => ['required', 'integer', 'min:0', 'max:1000'],
            'corrections_void_quick_max_count_per_day' => ['required', 'integer', 'min:0', 'max:1000'],
            'corrections_void_quick_window_minutes' => ['required', 'integer', 'min:0', 'max:1440'],
        ]);

        $setting = Setting::current();
        Setting::query()
            ->whereKey($setting->getKey())
            ->update([
                'rounding_base' => (int) $validated['rounding_base'],
                'discount_applies_before_tax' => (bool) $validated['discount_applies_before_tax'],
                'pos_default_customer_name' => trim((string) $validated['pos_default_customer_name']),
                'pos_default_payment_method' => trim((string) $validated['pos_default_payment_method']),
                'corrections_void_pending_requires_approval' => (bool) $validated['corrections_void_pending_requires_approval'],
                'corrections_refund_requires_approval_for_cash' => (bool) $validated['corrections_refund_requires_approval_for_cash'],
                'corrections_refund_quick_max_amount' => (int) $validated['corrections_refund_quick_max_amount'],
                'corrections_refund_quick_max_count_per_day' => (int) $validated['corrections_refund_quick_max_count_per_day'],
                'corrections_void_quick_max_count_per_day' => (int) $validated['corrections_void_quick_max_count_per_day'],
                'corrections_void_quick_window_minutes' => (int) $validated['corrections_void_quick_window_minutes'],
            ]);

        $this->dispatch('toast', type: 'success', message: 'Pengaturan sistem berhasil disimpan.');
    }

    public function savePointSettings(): void
    {
        $this->authorizeSectionEdit('points');

        $validated = $this->validate([
            'point_earning_rate' => ['required', 'numeric', 'min:0', 'max:99999999.9999', 'decimal:0,4'],
            'point_redemption_value' => ['required', 'numeric', 'min:0', 'max:9999999999.99', 'decimal:0,2'],
            'min_redemption_points' => ['required', 'integer', 'min:0', 'max:2147483647'],
        ]);

        $setting = Setting::current();
        Setting::query()
            ->whereKey($setting->getKey())
            ->update([
                'point_earning_rate' => round((float) $validated['point_earning_rate'], 4),
                'point_redemption_value' => round((float) $validated['point_redemption_value'], 2),
                'min_redemption_points' => (int) $validated['min_redemption_points'],
            ]);

        $this->dispatch('toast', type: 'success', message: 'Pengaturan poin berhasil disimpan.');
    }

    public function savePrinterSettings(): void
    {
        $this->authorizeSectionEdit('printers');

        $validated = $this->validate([
            'cashier_receipt_print_logo' => ['required', 'boolean'],
        ]);

        $setting = Setting::current();
        Setting::query()
            ->whereKey($setting->getKey())
            ->update([
                'cashier_receipt_print_logo' => (bool) $validated['cashier_receipt_print_logo'],
            ]);

        $this->dispatch('toast', type: 'success', message: 'Pengaturan printer berhasil disimpan.');
    }

    public function createPrinterSource(): void
    {
        $this->authorizeSectionEdit('printers');

        $validated = $this->validate([
            'printerName' => ['required', 'string', 'max:255', Rule::unique('printer_sources', 'name')],
            'printerType' => ['required', 'string', 'max:50'],
        ]);

        PrinterSource::query()->create([
            'name' => trim((string) $validated['printerName']),
            'type' => trim((string) $validated['printerType']),
        ]);

        $this->reset(['printerName', 'printerType']);
        $this->printerType = 'kasir';
        $this->resetValidation();

        $this->dispatch('toast', type: 'success', message: 'Sumber printer berhasil ditambahkan.');

        $this->dispatch('printer-sources-updated', sources: $this->printerSourcesForJs());
    }

    public function startEditPrinterSource(int $id): void
    {
        $this->authorizeSectionEdit('printers');

        $source = PrinterSource::query()->findOrFail($id);

        $this->editingPrinterSourceId = (int) $source->id;
        $this->editingPrinterName = (string) $source->name;
        $this->editingPrinterType = (string) $source->type;
        $this->resetValidation();
    }

    public function cancelEditPrinterSource(): void
    {
        $this->editingPrinterSourceId = null;
        $this->reset(['editingPrinterName', 'editingPrinterType']);
        $this->resetValidation();
    }

    public function updatePrinterSource(): void
    {
        $this->authorizeSectionEdit('printers');

        if (! $this->editingPrinterSourceId) {
            return;
        }

        $validated = $this->validate([
            'editingPrinterName' => [
                'required',
                'string',
                'max:255',
                Rule::unique('printer_sources', 'name')->ignore($this->editingPrinterSourceId),
            ],
            'editingPrinterType' => ['required', 'string', 'max:50'],
        ]);

        PrinterSource::query()
            ->whereKey($this->editingPrinterSourceId)
            ->update([
                'name' => trim((string) $validated['editingPrinterName']),
                'type' => trim((string) $validated['editingPrinterType']),
            ]);

        $this->cancelEditPrinterSource();
        $this->dispatch('toast', type: 'success', message: 'Sumber printer berhasil diperbarui.');

        $this->dispatch('printer-sources-updated', sources: $this->printerSourcesForJs());
    }

    public function deletePrinterSource(int $id): void
    {
        $this->authorizeSectionEdit('printers');

        $source = PrinterSource::query()->withCount('products')->findOrFail($id);

        if ((int) $source->products_count > 0) {
            $this->addError('printer', 'Sumber printer tidak bisa dihapus karena masih dipakai pada produk.');

            return;
        }

        $source->delete();

        if ($this->editingPrinterSourceId === $id) {
            $this->cancelEditPrinterSource();
        }

        $this->dispatch('toast', type: 'success', message: 'Sumber printer berhasil dihapus.');

        $this->dispatch('printer-sources-updated', sources: $this->printerSourcesForJs());
    }

    public function printerSourcesForUi()
    {
        return PrinterSource::query()
            ->withCount('products')
            ->orderBy('name')
            ->get();
    }

    public function printerSourcesForJs(): array
    {
        return PrinterSource::query()
            ->orderBy('name')
            ->get(['id', 'name', 'type'])
            ->map(fn (PrinterSource $s) => [
                'id' => (int) $s->id,
                'name' => (string) $s->name,
                'type' => (string) $s->type,
            ])
            ->all();
    }

    public function midtransStatus(): array
    {
        $merchantId = (string) config('midtrans.merchant_id', '');
        $clientKey = (string) config('midtrans.client_key', '');
        $serverKey = (string) config('midtrans.server_key', '');
        $isProduction = (bool) config('midtrans.is_production', false);

        $mask = function (string $value): string {
            $value = trim($value);
            if ($value === '') {
                return '-';
            }
            $len = strlen($value);
            if ($len <= 6) {
                return str_repeat('*', $len);
            }

            return substr($value, 0, 3).str_repeat('*', max(0, $len - 6)).substr($value, -3);
        };

        return [
            'is_ready' => $merchantId !== '' && $clientKey !== '' && $serverKey !== '',
            'is_production' => $isProduction,
            'merchant_id' => $mask($merchantId),
            'client_key' => $mask($clientKey),
            'server_key' => $mask($serverKey),
        ];
    }

    public function storeLogoUrl(): ?string
    {
        if (! $this->store_logo_path) {
            return null;
        }

        return Storage::disk('public')->url($this->store_logo_path);
    }

    public function render(): View
    {
        $this->authorize('settings.view');

        return view('livewire.settings.settings-page')->layout('layouts.app', ['title' => $this->title]);
    }
}
