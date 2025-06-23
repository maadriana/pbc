<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PbcRequest;
use App\Models\User;

class PbcCommentFactory extends Factory
{
    public function definition()
    {
        return [
            'pbc_request_id' => PbcRequest::factory(),
            'user_id' => User::factory(),
            'comment' => fake()->paragraph(),
            'is_internal' => fake()->boolean(30), // 30% chance of being internal
            'parent_id' => null,
            'attachments' => fake()->optional(0.2)->randomElements([
                'comments/attachment1.pdf',
                'comments/attachment2.jpg',
                'comments/attachment3.docx'
            ], fake()->numberBetween(1, 2)),
        ];
    }

    public function internal()
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => true,
            'user_id' => User::whereIn('role', ['system_admin', 'engagement_partner', 'manager', 'associate'])->inRandomOrder()->first()?->id,
        ]);
    }

    public function external()
    {
        return $this->state(fn (array $attributes) => [
            'is_internal' => false,
            'user_id' => User::where('role', 'guest')->inRandomOrder()->first()?->id,
        ]);
    }

    public function reply()
    {
        return $this->state(function (array $attributes) {
            $parentComment = \App\Models\PbcComment::inRandomOrder()->first();
            return [
                'parent_id' => $parentComment?->id,
                'pbc_request_id' => $parentComment?->pbc_request_id,
            ];
        });
    }
}
