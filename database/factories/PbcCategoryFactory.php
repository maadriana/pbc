<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PbcCategoryFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->words(3, true),
            'code' => fake()->unique()->lexify('???'),
            'description' => fake()->sentence(),
            'color_code' => fake()->hexColor(),
            'is_active' => true,
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
