<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SettingFactory extends Factory
{
    public function definition()
    {
        return [
            'key' => fake()->unique()->snake_case_word(),
            'value' => fake()->word(),
            'type' => fake()->randomElement(['string', 'integer', 'boolean', 'json']),
            'description' => fake()->sentence(),
            'is_public' => fake()->boolean(),
        ];
    }

    public function public()
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => true,
        ]);
    }

    public function private()
    {
        return $this->state(fn (array $attributes) => [
            'is_public' => false,
        ]);
    }

    public function boolean()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'boolean',
            'value' => fake()->boolean() ? 'true' : 'false',
        ]);
    }

    public function integer()
    {
        return $this->state(fn (array $attributes) => [
            'type' => 'integer',
            'value' => (string) fake()->numberBetween(1, 1000),
        ]);
    }
}
