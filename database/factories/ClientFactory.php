<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class ClientFactory extends Factory
{
    public function definition()
    {
        return [
            'name' => fake()->company(),
            'sec_registration_no' => 'SEC' . fake()->unique()->numerify('#########'),
            'industry_classification' => fake()->randomElement([
                'Manufacturing', 'Technology', 'Construction', 'Retail', 'Healthcare',
                'Finance', 'Education', 'Transportation', 'Real Estate', 'Services'
            ]),
            'business_address' => fake()->address(),
            'primary_contact_name' => fake()->name(),
            'primary_contact_email' => fake()->unique()->companyEmail(),
            'primary_contact_number' => '+63 917 ' . fake()->numerify('### ####'),
            'secondary_contact_name' => fake()->name(),
            'secondary_contact_email' => fake()->unique()->companyEmail(),
            'secondary_contact_number' => '+63 917 ' . fake()->numerify('### ####'),
            'is_active' => true,
        ];
    }

    public function inactive()
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    public function manufacturing()
    {
        return $this->state(fn (array $attributes) => [
            'industry_classification' => 'Manufacturing',
        ]);
    }

    public function technology()
    {
        return $this->state(fn (array $attributes) => [
            'industry_classification' => 'Technology',
        ]);
    }
}
