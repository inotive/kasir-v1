<?php

namespace App\Livewire\Users;

use App\Helpers\RbacLabelHelper;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithPagination;
use Spatie\Permission\Models\Role;

class UsersPage extends Component
{
    use WithPagination;

    public string $title = 'Manajemen Pengguna';

    public bool $createModalOpen = false;

    public bool $editModalOpen = false;

    public ?int $editingUserId = null;

    public string $name = '';

    public string $email = '';

    public string $role = '';

    public bool $isActive = true;

    public string $password = '';

    public string $passwordConfirmation = '';

    public string $managerPin = '';

    public string $editingName = '';

    public string $editingEmail = '';

    public string $editingRole = '';

    public bool $editingIsActive = true;

    public string $editingPassword = '';

    public string $editingPasswordConfirmation = '';

    public bool $editingManagerPinIsSet = false;

    public string $editingManagerPin = '';

    public function mount(): void
    {
        $this->authorize('users.view');
    }

    public function updatedRole(): void
    {
        $this->resetValidation('role');

        if (! $this->createNeedsManagerPin) {
            $this->managerPin = '';
            $this->resetValidation('managerPin');
        }
    }

    public function updatedEditingRole(): void
    {
        $this->resetValidation('editingRole');

        if (! $this->editNeedsManagerPin) {
            $this->editingManagerPin = '';
            $this->resetValidation('editingManagerPin');
        }
    }

    public function openCreateModal(): void
    {
        $this->authorize('users.create');

        $this->reset(['name', 'email', 'role', 'isActive', 'password', 'passwordConfirmation', 'managerPin']);
        $this->isActive = true;
        $this->resetValidation();
        $this->createModalOpen = true;
    }

    public function closeCreateModal(): void
    {
        $this->createModalOpen = false;
        $this->resetValidation();
    }

    public function createUser(): void
    {
        $this->authorize('users.create');

        $roles = Role::pluck('name')->toArray();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', 'string', Rule::in($roles)],
            'isActive' => ['boolean'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
            'managerPin' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $value = is_string($value) ? trim($value) : '';

                    if ($value !== '' && ! preg_match('/^\d{4,8}$/', $value)) {
                        $fail('Format PIN harus 4–8 angka.');
                    }
                },
            ],
        ]);

        $pin = trim((string) ($validated['managerPin'] ?? ''));
        $needsPin = $this->roleNeedsManagerPin((string) $validated['role']);

        if ($needsPin && $pin === '') {
            $this->addError('managerPin', 'PIN wajib diisi untuk peran yang punya akses persetujuan pembatalan/refund.');

            return;
        }

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
            'role' => $validated['role'], // Keep for backward compatibility
            'is_active' => (bool) $validated['isActive'],
        ]);

        // Assign Spatie Role
        $user->assignRole($validated['role']);

        if ($pin !== '') {
            $user->manager_pin = $pin;
            $user->manager_pin_set_at = now();
            $user->save();
        }

        $this->createModalOpen = false;
        $this->reset(['name', 'email', 'role', 'isActive', 'password', 'passwordConfirmation', 'managerPin']);
        $this->resetValidation();
        $this->dispatch('toast', type: 'success', message: 'Pengguna berhasil ditambahkan.');
    }

    public function startEdit(int $userId): void
    {
        $this->authorize('users.edit');

        $user = User::query()->with('roles')->findOrFail($userId);

        $this->editingUserId = (int) $user->id;
        $this->editingName = (string) $user->name;
        $this->editingEmail = (string) $user->email;
        // Get role from Spatie relation first, fallback to column
        $this->editingRole = $user->roles->first()?->name ?? (string) $user->role;
        $this->editingIsActive = (bool) $user->is_active;
        $this->editingPassword = '';
        $this->editingPasswordConfirmation = '';
        $this->editingManagerPinIsSet = $user->manager_pin_set_at !== null;
        $this->editingManagerPin = '';

        $this->resetValidation();
        $this->editModalOpen = true;
    }

    public function closeEditModal(): void
    {
        $this->editModalOpen = false;
        $this->editingUserId = null;
        $this->reset(['editingName', 'editingEmail', 'editingRole', 'editingIsActive', 'editingPassword', 'editingPasswordConfirmation', 'editingManagerPinIsSet', 'editingManagerPin']);
        $this->resetValidation();
    }

    public function updateUser(): void
    {
        $this->authorize('users.edit');

        if (! $this->editingUserId) {
            return;
        }

        $roles = Role::pluck('name')->toArray();

        $validated = $this->validate([
            'editingName' => ['required', 'string', 'max:255'],
            'editingEmail' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->editingUserId)],
            'editingRole' => ['required', 'string', Rule::in($roles)],
            'editingIsActive' => ['boolean'],
            'editingPassword' => ['nullable', 'string', 'min:8', 'max:255', 'same:editingPasswordConfirmation'],
            'editingPasswordConfirmation' => ['nullable', 'string'],
            'editingManagerPin' => [
                'nullable',
                'string',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $value = is_string($value) ? trim($value) : '';
                    if ($value === '') {
                        return;
                    }
                    if (! preg_match('/^\d{4,8}$/', $value)) {
                        $fail('Format PIN harus 4–8 angka.');
                    }
                },
            ],
        ]);

        $pin = trim((string) ($validated['editingManagerPin'] ?? ''));
        $needsPin = $this->roleNeedsManagerPin((string) $validated['editingRole']);

        if ($needsPin && ! $this->editingManagerPinIsSet && $pin === '') {
            $this->addError('editingManagerPin', 'PIN wajib diisi untuk peran yang punya akses persetujuan pembatalan/refund.');

            return;
        }

        if ((int) auth()->id() === (int) $this->editingUserId && $validated['editingIsActive'] === false) {
            $this->addError('editingIsActive', 'Tidak bisa menonaktifkan akun sendiri.');

            return;
        }

        $user = User::query()->findOrFail($this->editingUserId);

        $user->forceFill([
            'name' => $validated['editingName'],
            'email' => $validated['editingEmail'],
            'role' => $validated['editingRole'], // Keep for backward compatibility
            'is_active' => (bool) $validated['editingIsActive'],
        ]);

        // Sync Spatie Role
        $user->syncRoles([$validated['editingRole']]);

        if ($validated['editingPassword'] !== null && $validated['editingPassword'] !== '') {
            $user->password = $validated['editingPassword'];
        }

        if ($pin !== '') {
            $user->manager_pin = $pin;
            $user->manager_pin_set_at = now();
        }

        $user->save();

        $this->closeEditModal();
        $this->dispatch('toast', type: 'success', message: 'User berhasil diperbarui.');
    }

    public function deleteUser(int $userId): void
    {
        $this->authorize('users.delete');

        if ((int) auth()->id() === (int) $userId) {
            $this->dispatch('toast', type: 'error', message: 'Tidak bisa menghapus akun sendiri.');

            return;
        }

        $user = User::query()->findOrFail($userId);

        $isOwner = $user->hasRole('owner');
        if ($isOwner && User::role('owner')->whereKeyNot($userId)->doesntExist()) {
            $this->dispatch('toast', type: 'error', message: 'Minimal harus ada 1 akun Owner.');

            return;
        }

        $user->delete();

        if ($this->editingUserId === $userId) {
            $this->closeEditModal();
        }

        $this->dispatch('toast', type: 'success', message: 'User berhasil dihapus.');
        $this->resetPage();
    }

    protected function usersQuery(): Builder
    {
        return User::query()
            ->orderBy('name');
    }

    protected function roleNeedsManagerPin(string $roleName): bool
    {
        $roleName = trim($roleName);
        if ($roleName === '') {
            return false;
        }

        $roleModel = Role::query()->where('name', $roleName)->first();
        if (! $roleModel) {
            return false;
        }

        return $roleModel->hasAnyPermission(['transactions.void.approve', 'transactions.refund.approve']);
    }

    public function getCreateNeedsManagerPinProperty(): bool
    {
        return $this->roleNeedsManagerPin($this->role);
    }

    public function getEditNeedsManagerPinProperty(): bool
    {
        return $this->roleNeedsManagerPin($this->editingRole);
    }

    public function render(): View
    {
        $this->authorize('users.view');

        $users = $this->usersQuery()->with('roles')->paginate(15);
        $roles = Role::orderBy('name')
            ->pluck('name')
            ->mapWithKeys(fn (string $name) => [$name => RbacLabelHelper::role($name)])
            ->all();

        return view('livewire.users.users-page', [
            'users' => $users,
            'roleLabels' => $roles, // Use dynamic roles from DB
            'createNeedsManagerPin' => $this->createNeedsManagerPin,
            'editNeedsManagerPin' => $this->editNeedsManagerPin,
        ])->layout('layouts.app', ['title' => $this->title]);
    }
}
