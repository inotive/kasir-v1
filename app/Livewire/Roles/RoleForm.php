<?php

namespace App\Livewire\Roles;

use Livewire\Component;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleForm extends Component
{
    public ?Role $role = null;

    public $name = '';

    public $selectedPermissions = [];

    public $isEdit = false;

    public function mount(?Role $role = null)
    {
        $this->authorize('roles.manage');

        if ($role && $role->exists) {
            $this->role = $role;
            $this->name = $role->name;
            $this->selectedPermissions = $role->permissions->pluck('name')->toArray();
            $this->isEdit = true;
        }
    }

    public function save()
    {
        $this->authorize('roles.manage');

        $this->validate([
            'name' => 'required|unique:roles,name,'.($this->role?->id ?? 'NULL'),
            'selectedPermissions' => 'array',
        ]);

        if ($this->isEdit) {
            $this->role->update(['name' => $this->name]);
            $this->role->syncPermissions($this->selectedPermissions);
            $message = 'Peran berhasil diperbarui.';
        } else {
            $role = Role::create(['name' => $this->name]);
            $role->syncPermissions($this->selectedPermissions);
            $message = 'Peran berhasil dibuat.';
        }

        $this->redirectRoute('roles.index', navigate: true);
        $this->dispatch('toast', type: 'success', message: $message);
    }

    public function render()
    {
        $this->authorize('roles.manage');

        $permissions = Permission::all();
        $groupedPermissions = $permissions->groupBy(function ($item) {
            return explode('.', $item->name)[0]; // Group by prefix (e.g., 'products', 'pos')
        });

        return view('livewire.roles.role-form', [
            'groupedPermissions' => $groupedPermissions,
        ])->layout('layouts.app', ['title' => $this->isEdit ? 'Ubah Peran' : 'Buat Peran Baru']);
    }
}
