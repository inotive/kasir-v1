<?php

namespace App\Livewire\Tenants;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class TenantFormPage extends Component
{
    public string $title = 'Buat Tenant';

    public ?Tenant $tenant = null;

    public string $name = '';

    public string $slug = '';

    public string $businessName = '';

    public string $ownerName = '';

    public string $ownerEmail = '';

    public string $ownerPassword = '';

    public string $ownerPasswordConfirmation = '';

    public bool $isEditing = false;

    public function mount(?Tenant $tenant = null): void
    {
        $this->authorize('dashboard.access');

        if (auth()->user()->tenant_id !== null) {
            abort(403);
        }

        if ($tenant && $tenant->exists) {
            $this->isEditing = true;
            $this->tenant = $tenant;
            $this->title = 'Edit Tenant';
            $this->name = $tenant->name;
            $this->slug = $tenant->slug;
            $this->businessName = $tenant->business_name ?? '';
        }
    }

    public function save(): mixed
    {
        $this->authorize('dashboard.access');

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', $this->isEditing ? 'unique:tenants,slug,'.$this->tenant->id : 'unique:tenants,slug'],
            'businessName' => ['nullable', 'string', 'max:255'],
            'ownerName' => [$this->isEditing ? 'nullable' : 'required', 'string', 'max:255'],
            'ownerEmail' => [$this->isEditing ? 'nullable' : 'required', 'email', 'max:255'],
            'ownerPassword' => [$this->isEditing ? 'nullable' : 'required', 'string', 'min:8', 'max:255', 'same:ownerPasswordConfirmation'],
            'ownerPasswordConfirmation' => [$this->isEditing ? 'nullable' : 'required', 'string'],
        ]);

        if ($this->isEditing) {
            $this->tenant->update([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'business_name' => $validated['businessName'],
            ]);

            session()->flash('toast', 'Tenant berhasil diperbarui.');
        } else {
            $appDomain = parse_url(config('app.url'), PHP_URL_HOST);

            $tenant = Tenant::query()->create([
                'name' => $validated['name'],
                'slug' => $validated['slug'],
                'business_name' => $validated['businessName'],
                'domain' => $validated['slug'].'.'.$appDomain,
            ]);

            $user = User::query()->create([
                'name' => $validated['ownerName'],
                'email' => $validated['ownerEmail'],
                'password' => Hash::make($validated['ownerPassword']),
                'tenant_id' => $tenant->id,
                'role' => 'owner',
                'is_active' => true,
            ]);

            $user->assignRole('owner');

            session()->flash('toast', 'Tenant berhasil dibuat. Owner dapat login di '.$tenant->slug.'.localhost:8000/admin/signin');
        }

        return redirect()->route('tenants.index');
    }

    public function render(): View
    {
        return view('livewire.tenants.tenant-form-page')
            ->layout('layouts.app', ['title' => $this->title]);
    }
}
