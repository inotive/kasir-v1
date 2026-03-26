<?php

use App\Livewire\SelfOrder\Pages\StartPage;
use App\Models\DiningTable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(RefreshDatabase::class);

it('shows Indonesian per-field validation messages on self-order registration', function () {
    $table = DiningTable::query()->create([
        'table_number' => 'A1',
        'image' => null,
        'qr_value' => 'TBL-A1',
    ]);

    session([
        'dining_table_id' => $table->id,
        'self_order_token' => Str::random(40),
    ]);

    Livewire::test(StartPage::class)
        ->set('tab', 'register')
        ->call('registerMember')
        ->assertHasErrors(['register_name', 'register_email', 'register_phone', 'register_province', 'register_regency', 'register_district'])
        ->assertSee('nama wajib diisi')
        ->assertSee('email wajib diisi')
        ->assertSee('no. WhatsApp wajib diisi');
});
