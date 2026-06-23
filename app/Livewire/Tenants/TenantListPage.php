<?php

namespace App\Livewire\Tenants;

use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TenantListPage extends Component
{
    use WithPagination;

    public string $title = 'Kelola Tenant';

    public function mount(): void
    {
        $this->authorize('dashboard.access');

        if (auth()->user()->tenant_id !== null) {
            abort(403);
        }
    }

    public function toggleActive(int $tenantId): void
    {
        $this->authorize('dashboard.access');

        if (auth()->user()->tenant_id !== null) {
            abort(403);
        }

        $tenant = Tenant::findOrFail($tenantId);
        $tenant->update(['is_active' => ! $tenant->is_active]);

        session()->flash('toast', 'Status tenant berhasil diperbarui.');
    }

    public function render(): View
    {
        $tenants = Tenant::query()
            ->withCount('users')
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.tenants.tenant-list-page', ['tenants' => $tenants])
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
