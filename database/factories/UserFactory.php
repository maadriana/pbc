<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;

class UserFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
            'entity' => fake()->company(),
            'role' => fake()->randomElement(['system_admin', 'engagement_partner', 'manager', 'associate', 'guest']),
            'access_level' => fake()->numberBetween(1, 5),
            'contact_number' => '+63 917 ' . fake()->numerify('### ####'),
            'is_active' => true,
            'remember_token' => Str::random(10),
        ];
    }

    public function systemAdmin()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'system_admin',
            'access_level' => 1,
            'entity' => 'PBC Audit System',
        ]);
    }

    public function engagementPartner()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'engagement_partner',
            'access_level' => 2,
            'entity' => 'Smith & Associates CPA',
        ]);
    }

    public function manager()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'manager',
            'access_level' => 3,
            'entity' => 'Smith & Associates CPA',
        ]);
    }

    public function associate()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'associate',
            'access_level' => 4,
            'entity' => 'Smith & Associates CPA',
        ]);
    }

    public function guest()
    {
        return $this->state(fn (array $attributes) => [
            'role' => 'guest',
            'access_level' => 5,
            'entity' => fake()->company(),
        ]);
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function unverified()
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
