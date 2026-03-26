<?php

namespace App\Livewire\Auth;

use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Component;

class InitialSetupPage extends Component
{
    public string $title = 'Setup Awal';

    public string $name = '';

    public string $email = '';

    public string $password = '';

    public string $passwordConfirmation = '';

    public function mount(): mixed
    {
        if (User::exists()) {
            return redirect()->route('signin');
        }

        return null;
    }

    public function setup(): mixed
    {
        if (User::exists()) {
            return redirect()->route('signin');
        }

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'same:passwordConfirmation'],
            'passwordConfirmation' => ['required', 'string'],
        ]);

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => 'owner',
            'is_active' => true,
            'last_login_at' => now(),
        ]);

        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('dashboard');
    }

    public function render(): View
    {
        return view('livewire.auth.initial-setup-page')
            ->layout('layouts.fullscreen-layout', ['title' => $this->title]);
    }
}
