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
