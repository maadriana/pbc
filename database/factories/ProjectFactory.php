<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Client;
use App\Models\User;

class ProjectFactory extends Factory
{
    public function definition()
    {
        $engagementPartners = User::where('role', 'engagement_partner')->pluck('id');
        $managers = User::where('role', 'manager')->pluck('id');
        $associates = User::where('role', 'associate')->pluck('id');

        return [
            'client_id' => Client::factory(),
            'engagement_type' => fake()->randomElement(['audit', 'accounting', 'tax', 'special_engagement', 'others']),
            'engagement_period' => fake()->dateTimeBetween('2024-01-01', '2024-12-31'),
            'contact_person' => fake()->name(),
            'contact_email' => fake()->companyEmail(),
            'contact_number' => '+63 917 ' . fake()->numerify('### ####'),
            'engagement_partner_id' => $engagementPartners->isNotEmpty() ? $engagementPartners->random() : null,
            'manager_id' => $managers->isNotEmpty() ? $managers->random() : null,
            'associate_1_id' => $associates->isNotEmpty() ? $associates->random() : null,
            'associate_2_id' => $associates->count() > 1 ? $associates->random() : null,
            'status' => fake()->randomElement(['active', 'completed', 'on_hold', 'cancelled']),
            'progress_percentage' => fake()->randomFloat(2, 0, 100),
            'notes' => fake()->optional()->paragraph(),
        ];
    }

    public function active()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'active',
        ]);
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

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'progress_percentage' => 100.00,
        ]);
    }
}
