<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\Project;
use App\Models\PbcCategory;
use App\Models\User;
use Carbon\Carbon;

class PbcRequestFactory extends Factory
{
    public function definition()
    {
        $requestors = User::whereIn('role', ['manager', 'associate'])->pluck('id');
        $assignees = User::where('role', 'guest')->pluck('id');
        $dateRequested = fake()->dateTimeBetween('-30 days', 'now');
        $dueDate = Carbon::parse($dateRequested)->addDays(fake()->numberBetween(3, 14));

        return [
            'project_id' => Project::factory(),
            'category_id' => PbcCategory::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'requestor_id' => $requestors->isNotEmpty() ? $requestors->random() : User::factory(),
            'assigned_to_id' => $assignees->isNotEmpty() ? $assignees->random() : User::factory()->guest(),
            'date_requested' => $dateRequested,
            'due_date' => $dueDate,
            'status' => fake()->randomElement(['pending', 'in_progress', 'completed', 'overdue', 'rejected']),
            'priority' => fake()->randomElement(['low', 'medium', 'high', 'urgent']),
            'notes' => fake()->optional()->paragraph(),
            'rejection_reason' => fake()->optional(0.1)->sentence(),
            'completed_at' => fake()->optional(0.3)->dateTimeBetween($dateRequested, 'now'),
            'approved_by' => fake()->optional(0.3)->randomElement(User::whereIn('role', ['manager', 'engagement_partner'])->pluck('id')->toArray()),
            'approved_at' => fake()->optional(0.3)->dateTimeBetween($dateRequested, 'now'),
        ];
    }

    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'completed_at' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function completed()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'completed',
            'completed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'approved_by' => User::whereIn('role', ['manager', 'engagement_partner'])->inRandomOrder()->first()?->id,
            'approved_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function overdue()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'overdue',
            'due_date' => fake()->dateTimeBetween('-14 days', '-1 day'),
            'completed_at' => null,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function urgent()
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'urgent',
        ]);
    }

    public function high()
    {
        return $this->state(fn (array $attributes) => [
            'priority' => 'high',
        ]);
    }
}
