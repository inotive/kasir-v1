<?php

namespace App\Livewire\Auth;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class SignInPage extends Component
{
    public string $title = 'Sign In';

    public string $email = '';

    public string $password = '';

    public bool $remember = false;

    public bool $isTenantContext = false;

    public ?string $tenantName = null;

    public function mount(): mixed
    {
        $this->isTenantContext = Tenant::checkCurrent();

        if ($this->isTenantContext) {
            $this->tenantName = Tenant::current()->name;
            $tenantHasUsers = User::where('tenant_id', Tenant::current()->id)->exists();

            if (! $tenantHasUsers) {
                return redirect()->route('setup');
            }

            return null;
        }

        if (User::whereNull('tenant_id')->doesntExist()) {
            return redirect()->route('setup');
        }

        return null;
    }

    public function signIn(): mixed
    {
        $validated = $this->validate([
            'email' => ['required', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
            'remember' => ['boolean'],
        ]);

        $query = User::query()->where('email', $validated['email']);

        if ($this->isTenantContext) {
            $query->where('tenant_id', Tenant::current()->id);
        } else {
            $query->whereNull('tenant_id');
        }

        $user = $query->first();

        if ($user && ! $user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun dinonaktifkan.',
            ]);
        }

        if ($user && $user->tenant_id !== null && $user->tenant && ! $user->tenant->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Akun bisnis ini sedang dinonaktifkan.',
            ]);
        }

        $ok = false;
        try {
            $ok = Auth::attempt(
                ['email' => $validated['email'], 'password' => $validated['password']],
                (bool) $validated['remember'],
            );
        } catch (\Throwable) {
            $ok = false;
        }

        if (! $ok && $user) {
            $stored = (string) $user->password;
            $input = (string) $validated['password'];

            if (! str_starts_with($stored, '$') && hash_equals($stored, $input)) {
                $user->password = Hash::make($input);
                $user->save();

                Auth::login($user, (bool) $validated['remember']);
                $ok = true;
            }
        }

        if (! $ok) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        request()->session()->regenerate();

        User::query()->whereKey(Auth::id())->update(['last_login_at' => now()]);

        return redirect()->intended(route('dashboard'));
    }

    public function render(): View
    {
        return view('livewire.auth.sign-in-page')
            ->layout('layouts.fullscreen-layout', ['title' => $this->title]);
    }
}
