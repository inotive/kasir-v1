<?php

namespace App\Livewire\Tenants;

use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use Illuminate\Contracts\View\View;
use Livewire\Component;
use Livewire\WithPagination;

class TenantListPage extends Component
{
    use WithPagination;

    public string $title = 'Kelola Tenant';

    public string $statusFilter = 'active';

    public function updatingStatusFilter(): void
    {
        $this->resetPage();
    }

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

        if ($tenant->is_active) {
            $suffix = '-deactivated-'.now()->timestamp;

            $tenant->update([
                'is_active' => false,
                'slug' => $tenant->slug.$suffix,
                'domain' => $tenant->domain ? $tenant->domain.$suffix : $tenant->domain,
            ]);
        } else {
            $tenant->update(['is_active' => true]);
        }

        session()->flash('toast', 'Status tenant berhasil diperbarui.');
    }

    public function render(): View
    {
        $tenants = Tenant::query()
            ->when($this->statusFilter === 'active', fn ($q) => $q->where('is_active', true))
            ->withCount(['users' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)])
            ->with(['users' => fn ($q) => $q->withoutGlobalScope(TenantScope::class)->where('role', 'owner')])
            ->orderBy('name')
            ->paginate(15);

        return view('livewire.tenants.tenant-list-page', ['tenants' => $tenants])
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
