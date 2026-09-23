<?php

use App\Livewire\Settings\SettingsPage;
use App\Models\User;
use App\Models\WhatsappSetting;
use Database\Seeders\RolePermissionSeeder;
use Livewire\Livewire;

test('owner can view and toggle the whatsapp settings section', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('owner');
    $this->actingAs($user);

    Livewire::test(SettingsPage::class)
        ->call('setSection', 'whatsapp')
        ->assertSet('activeSection', 'whatsapp')
        ->set('whatsapp_is_enabled', true)
        ->set('whatsapp_send_on_checkout_default', false)
        ->call('saveWhatsappToggles')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('whatsapp_settings', [
        'id' => WhatsappSetting::current()->id,
        'is_enabled' => true,
        'send_on_checkout_default' => false,
    ]);
});

test('the QR panel renders inline, not as a fixed overlay modal', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('owner');
    $this->actingAs($user);

    Livewire::test(SettingsPage::class)
        ->call('setSection', 'whatsapp')
        ->set('whatsappQrPanelOpen', true)
        ->assertSee('Scan QR di WhatsApp')
        ->assertDontSee('z-99999');
});

test('cashier cannot view the whatsapp settings section', function () {
    $this->seed(RolePermissionSeeder::class);

    $user = User::factory()->create(['is_active' => true]);
    $user->assignRole('cashier');
    $this->actingAs($user);

    Livewire::test(SettingsPage::class)
        ->call('setSection', 'whatsapp')
        ->assertSet('activeSection', fn (string $value) => $value !== 'whatsapp');
});
