<?php

namespace Database\Factories;

use App\Models\StudentDocument;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StudentDocument>
 */
class StudentDocumentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'document_requirement_id' => null,
            'title' => fake()->sentence(3),
            'category' => 'Signed form',
            'storage_path' => 'ojt-documents/example.pdf',
            'original_name' => 'example.pdf',
            'mime_type' => 'application/pdf',
            'size' => 1024,
            'review_status' => 'pending',
            'reviewer_id' => null,
            'review_notes' => null,
            'reviewed_at' => null,
        ];
    }
}
