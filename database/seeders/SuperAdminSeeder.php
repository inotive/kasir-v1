<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => 'superadmin@gmail.com'],
            [
                'name' => 'Super Admin',
                'password' => '123123123',
                'role' => 'owner',
                'is_active' => true,
            ]
        );

        if (! $user->hasRole('owner')) {
            $user->assignRole('owner');
        }
    }
}
