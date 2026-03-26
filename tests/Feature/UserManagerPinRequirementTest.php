<?php

use App\Livewire\Users\UsersPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

it('requires manager pin when creating owner admin or manager users', function () {
    $this->seed(RolePermissionSeeder::class);

    $owner = User::query()->create([
        'name' => 'Owner',
        'email' => 'owner-users@example.test',
        'password' => 'password',
        'role' => 'owner',
        'is_active' => true,
        'manager_pin' => '1234',
    ]);
    $owner->assignRole('owner');

    Livewire::actingAs($owner)
        ->test(UsersPage::class)
        ->call('openCreateModal')
        ->set('name', 'New Manager')
        ->set('email', 'new-manager@example.test')
        ->set('role', 'manager')
        ->set('isActive', true)
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->call('createUser')
        ->assertHasErrors(['managerPin']);
});

it('stores hashed manager pin when creating manager user', function () {
    $this->seed(RolePermissionSeeder::class);

    $owner = User::query()->create([
        'name' => 'Owner',
        'email' => 'owner-users2@example.test',
        'password' => 'password',
        'role' => 'owner',
        'is_active' => true,
        'manager_pin' => '1234',
    ]);
    $owner->assignRole('owner');

    Livewire::actingAs($owner)
        ->test(UsersPage::class)
        ->call('openCreateModal')
        ->set('name', 'New Manager')
        ->set('email', 'new-manager2@example.test')
        ->set('role', 'manager')
        ->set('managerPin', '5678')
        ->set('isActive', true)
        ->set('password', 'password123')
        ->set('passwordConfirmation', 'password123')
        ->call('createUser')
        ->assertHasNoErrors();

    $created = User::query()->where('email', 'new-manager2@example.test')->first();
    expect($created)->not->toBeNull();
    expect($created->manager_pin_set_at)->not->toBeNull();
    expect((string) ($created->manager_pin ?? ''))->not->toBe('5678');
});
