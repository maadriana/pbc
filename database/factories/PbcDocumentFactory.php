<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use App\Models\PbcRequest;
use App\Models\User;

class PbcDocumentFactory extends Factory
{
    public function definition()
    {
        $uploaders = User::where('role', 'guest')->pluck('id');
        $reviewers = User::whereIn('role', ['manager', 'engagement_partner'])->pluck('id');
        $fileTypes = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'jpg', 'png'];
        $fileType = fake()->randomElement($fileTypes);

        return [
            'pbc_request_id' => PbcRequest::factory(),
            'original_name' => fake()->words(3, true) . '.' . $fileType,
            'file_name' => fake()->uuid() . '.' . $fileType,
            'file_path' => 'pbc-documents/' . fake()->uuid() . '.' . $fileType,
            'file_type' => $fileType,
            'file_size' => fake()->numberBetween(1024, 10485760), // 1KB to 10MB
            'mime_type' => $this->getMimeType($fileType),
            'uploaded_by' => $uploaders->isNotEmpty() ? $uploaders->random() : User::factory()->guest(),
            'status' => fake()->randomElement(['pending', 'approved', 'rejected']),
            'comments' => fake()->optional()->sentence(),
            'reviewed_by' => fake()->optional(0.7)->randomElement($reviewers->toArray()),
            'reviewed_at' => fake()->optional(0.7)->dateTimeBetween('-7 days', 'now'),
            'version' => fake()->randomElement(['1.0', '1.1', '2.0', '2.1']),
            'is_latest_version' => true,
        ];
    }

    public function approved()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'reviewed_by' => User::whereIn('role', ['manager', 'engagement_partner'])->inRandomOrder()->first()?->id,
            'reviewed_at' => fake()->dateTimeBetween('-7 days', 'now'),
        ]);
    }

    public function rejected()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'rejected',
            'reviewed_by' => User::whereIn('role', ['manager', 'engagement_partner'])->inRandomOrder()->first()?->id,
            'reviewed_at' => fake()->dateTimeBetween('-7 days', 'now'),
            'comments' => 'Document rejected: ' . fake()->sentence(),
        ]);
    }

    public function pending()
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'reviewed_by' => null,
            'reviewed_at' => null,
        ]);
    }

    private function getMimeType($fileType)
    {
        $mimeTypes = [
            'pdf' => 'application/pdf',
            'doc' => 'application/msword',
            'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'xls' => 'application/vnd.ms-excel',
            'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
        ];

        return $mimeTypes[$fileType] ?? 'application/octet-stream';
    }
}
