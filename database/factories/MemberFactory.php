<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class MemberFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'verification_token' => null,
            'phone' => fake()->unique()->e164PhoneNumber(),
            'member_region_id' => null,
            'member_type' => 'umum',
            'points' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    public function unverifiedEmail(): static
    {
        return $this->state(fn () => [
            'email_verified_at' => null,
            'verification_token' => Str::random(40),
        ]);
    }
}
