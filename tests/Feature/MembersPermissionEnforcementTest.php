<?php

use App\Livewire\Members\MemberRegionsPage;
use App\Livewire\Members\MembersPage;
use App\Livewire\SelfOrder\Pages\VerifyEmailPage;
use App\Models\Member;
use App\Models\User;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('members list hides PII and blocks PII search without members.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('members_viewer', $guard);
    $role->syncPermissions(['members.view']);

    $user = User::factory()->create();
    $user->assignRole('members_viewer');

    $member = Member::query()->create([
        'name' => 'Alice',
        'email' => 'alice@example.com',
        'phone' => '081234567890',
        'points' => 0,
        'member_region_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(MembersPage::class)
        ->assertSee('Alice')
        ->assertDontSee('081234567890')
        ->assertDontSee('alice@example.com')
        ->set('search', '081234567890')
        ->assertDontSee('Alice');

    Livewire::actingAs($user)
        ->test(MembersPage::class)
        ->call('openCreateMemberModal')
        ->assertStatus(403);
});

test('members list allows PII view and search with members.pii.view', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('members_pii_viewer', $guard);
    $role->syncPermissions(['members.view', 'members.pii.view']);

    $user = User::factory()->create();
    $user->assignRole('members_pii_viewer');

    Member::query()->create([
        'name' => 'Bob',
        'email' => 'bob@example.com',
        'phone' => '089999000111',
        'points' => 0,
        'member_region_id' => null,
    ]);

    Livewire::actingAs($user)
        ->test(MembersPage::class)
        ->set('search', '089999000111')
        ->assertSee('Bob')
        ->assertSee('089999000111');
});

test('member regions viewer cannot import or delete', function () {
    $this->seed(RolePermissionSeeder::class);

    $guard = (string) config('auth.defaults.guard', 'web');
    $role = Role::findOrCreate('member_regions_viewer', $guard);
    $role->syncPermissions(['members.regions.view']);

    $user = User::factory()->create();
    $user->assignRole('member_regions_viewer');

    Livewire::actingAs($user)
        ->test(MemberRegionsPage::class)
        ->assertStatus(200)
        ->assertDontSee('Import GeoJSON')
        ->call('openImportModal')
        ->assertStatus(403);
});

test('verify email page does not accept lookup from query params', function () {
    Livewire::withQueryParams(['mid' => 1, 'email' => 'someone@example.com'])
        ->test(VerifyEmailPage::class)
        ->assertRedirect(route('self-order.start'));
});

test('verify email page masks email from session', function () {
    $this->withSession(['pending_member_email' => 'alice@example.com']);

    Livewire::test(VerifyEmailPage::class)
        ->assertSee('a***@example.com');
});
