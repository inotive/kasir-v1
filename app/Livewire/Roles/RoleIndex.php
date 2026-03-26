<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleIndex extends Component
{
    use WithPagination;

    public string $search = '';

    public string $permissionSearch = '';

    public string $tab = 'list';

    public function mount(): void
    {
        $this->authorize('roles.view');
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedPermissionSearch(): void {}

    public function setTab(string $tab): void
    {
        $tab = trim($tab);
        if (! in_array($tab, ['list', 'guide', 'permissions'], true)) {
            $tab = 'list';
        }

        $this->tab = $tab;
        $this->resetPage();
    }

    public function render()
    {
        $this->authorize('roles.view');

        $roles = collect();
        $guideRoles = collect();
        $permissions = collect();

        if ($this->tab === 'permissions') {
            $term = trim($this->permissionSearch);
            $permissions = Permission::query()
                ->when($term !== '', fn ($q) => $q->where('name', 'like', '%'.$term.'%'))
                ->orderBy('name')
                ->get();
        } elseif ($this->tab === 'guide') {
            $guideRoles = Role::query()
                ->with('permissions')
                ->orderBy('name')
                ->get();
        } else {
            $roles = Role::query()
                ->when($this->search !== '', fn ($q) => $q->where('name', 'like', '%'.$this->search.'%'))
                ->withCount('permissions', 'users')
                ->orderBy('name')
                ->paginate(10);
        }

        return view('livewire.roles.role-index', [
            'roles' => $roles,
            'guideRoles' => $guideRoles,
            'permissions' => $permissions,
        ])->layout('layouts.app', ['title' => 'Peran & Hak Akses']);
    }

    public function delete($id)
    {
        $this->authorize('roles.manage');

        $role = Role::find($id);
        if ($role) {
            if ($role->name === 'owner') {
                $this->dispatch('toast', type: 'error', message: 'Peran Owner tidak bisa dihapus.');

                return;
            }
            if ($role->users()->count() > 0) {
                $this->dispatch('toast', type: 'error', message: 'Peran masih dipakai oleh pengguna.');

                return;
            }
            $role->delete();
            $this->dispatch('toast', type: 'success', message: 'Peran berhasil dihapus.');
        }
    }
}
