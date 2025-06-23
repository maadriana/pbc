<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PbcCategory;
use App\Models\User;

class PbcTemplateFactory extends Factory
{
    public function definition()
    {
        $creators = User::whereIn('role', ['system_admin', 'engagement_partner', 'manager'])->pluck('id');

        return [
            'name' => fake()->sentence(3),
            'description' => fake()->paragraph(),
            'category_id' => PbcCategory::factory(),
            'engagement_type' => fake()->randomElement(['audit', 'accounting', 'tax', 'special_engagement', 'others']),
            'default_description' => fake()->paragraph(3),
            'default_days_to_complete' => fake()->numberBetween(3, 14),
            'default_priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'required_fields' => [
                'account_numbers' => fake()->boolean() ? 'required' : 'optional',
                'period_covered' => 'required',
                'additional_notes' => 'optional',
            ],
            'is_active' => true,
            'created_by' => $creators->isNotEmpty() ? $creators->random() : User::factory()->systemAdmin(),
        ];
    }

    public function audit()
    {
        return $this->state(fn (array $attributes) => [
            'engagement_type' => 'audit',
        ]);
    }

    public function tax()
    {
        return $this->state(fn (array $attributes) => [
            'engagement_type' => 'tax',
        ]);
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
