<?php

use App\Livewire\Roles\RoleForm;
use App\Livewire\Roles\RoleIndex;
use App\Livewire\Users\UsersPage;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('users page denies access without users.view and blocks create modal without users.create', function () {
    $this->seed(RolePermissionSeeder::class);

    $userNoUsersPerm = User::factory()->create();
    $userNoUsersPerm->assignRole('waiter');

    Livewire::actingAs($userNoUsersPerm)
        ->test(UsersPage::class)
        ->assertStatus(403);

    $guard = (string) config('auth.defaults.guard', 'web');
    $viewer = Role::findOrCreate('users_viewer_only', $guard);
    $viewer->syncPermissions(['users.view']);

    $userViewer = User::factory()->create();
    $userViewer->assignRole('users_viewer_only');

    Livewire::actingAs($userViewer)
        ->test(UsersPage::class)
        ->assertStatus(200)
        ->call('openCreateModal')
        ->assertStatus(403);
});

test('roles pages deny access without roles permissions', function () {
    $this->seed(RolePermissionSeeder::class);

    $userNoRolePerm = User::factory()->create();
    $userNoRolePerm->assignRole('waiter');

    Livewire::actingAs($userNoRolePerm)
        ->test(RoleIndex::class)
        ->assertStatus(403);

    $guard = (string) config('auth.defaults.guard', 'web');
    $viewer = Role::findOrCreate('roles_viewer_only', $guard);
    $viewer->syncPermissions(['roles.view']);

    $userViewer = User::factory()->create();
    $userViewer->assignRole('roles_viewer_only');

    Livewire::actingAs($userViewer)
        ->test(RoleIndex::class)
        ->assertStatus(200);

    Livewire::actingAs($userViewer)
        ->test(RoleForm::class)
        ->assertStatus(403);
});
