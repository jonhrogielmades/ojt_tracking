<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityLogControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_downloading_another_students_activity_attachment_returns_404(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $path = "activity-attachments/{$otherStudent->id}/evidence.pdf";
        Storage::disk('local')->put($path, 'private evidence');
        $activity = ActivityLog::factory()->for($otherStudent)->create([
            'attachment_path' => $path,
            'attachment_original_name' => 'evidence.pdf',
            'attachment_mime_type' => 'application/pdf',
            'attachment_size' => 16,
        ]);

        $response = $this->actingAs($student)->get(route('activities.attachment', $activity));

        $response->assertNotFound();
    }

    public function test_exact_attachment_submission_retry_returns_success_without_duplicate_record_or_file(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $payload = $this->attachmentPayload('66666666-6666-4666-8666-666666666666');

        $createdResponse = $this->actingAs($student)
            ->withHeader('Accept', 'application/json')
            ->post(route('activities.store'), [
                ...$payload,
                'attachment' => UploadedFile::fake()->create('evidence.pdf', 32, 'application/pdf'),
            ]);
        $retryResponse = $this->withHeader('Accept', 'application/json')
            ->post(route('activities.store'), [
                ...$payload,
                'attachment' => UploadedFile::fake()->create('evidence.pdf', 32, 'application/pdf'),
            ]);

        $createdResponse->assertCreated();
        $createdResponse->assertJsonPath('status', 'synced');
        $retryResponse->assertOk();
        $retryResponse->assertJsonPath('status', 'synced');
        $retryResponse->assertJsonPath('message', 'This activity submission was already saved.');

        $this->assertDatabaseCount('activity_logs', 1);
        $activity = ActivityLog::query()->whereBelongsTo($student)->sole();
        Storage::disk('local')->assertExists($activity->attachment_path);
        $this->assertCount(1, Storage::disk('local')->allFiles("activity-attachments/{$student->id}"));
    }

    public function test_changed_attachment_submission_with_used_key_returns_409_without_replacing_attachment(): void
    {
        Storage::fake('local');
        $student = User::factory()->create();
        $payload = $this->attachmentPayload('77777777-7777-4777-8777-777777777777');
        $this->actingAs($student)
            ->withHeader('Accept', 'application/json')
            ->post(route('activities.store'), [
                ...$payload,
                'attachment' => UploadedFile::fake()->create('original.pdf', 32, 'application/pdf'),
            ])
            ->assertCreated();
        $activity = ActivityLog::query()->whereBelongsTo($student)->sole();
        $originalPath = $activity->attachment_path;

        $response = $this->withHeader('Accept', 'application/json')
            ->post(route('activities.store'), [
                ...$payload,
                'tasks_completed' => 'Changed tasks for a reused submission key.',
                'attachment' => UploadedFile::fake()->create('replacement.pdf', 32, 'application/pdf'),
            ]);

        $response->assertConflict();
        $response->assertJsonPath('status', 'conflict');
        $response->assertJsonPath('conflict_type', 'idempotency_key');

        $this->assertDatabaseCount('activity_logs', 1);
        $this->assertSame($originalPath, $activity->refresh()->attachment_path);
        $this->assertSame('Documented the completed integration work.', $activity->tasks_completed);
        Storage::disk('local')->assertExists($originalPath);
        $this->assertCount(1, Storage::disk('local')->allFiles("activity-attachments/{$student->id}"));
    }

    /**
     * @return array<string, string>
     */
    private function attachmentPayload(string $idempotencyKey): array
    {
        return [
            'activity_date' => '2026-10-20',
            'tasks_completed' => 'Documented the completed integration work.',
            'skills_tools' => 'Laravel, PHPUnit',
            'lessons_learned' => 'Retries must be idempotent.',
            'challenges_notes' => 'Verified private storage behavior.',
            'idempotency_key' => $idempotencyKey,
        ];
    }
}
