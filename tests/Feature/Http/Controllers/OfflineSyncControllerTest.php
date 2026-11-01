<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OfflineSyncControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_same_dtr_draft_retry_returns_existing_record_without_duplicate(): void
    {
        $user = User::factory()->create();
        $payload = $this->dtrPayload('11111111-1111-4111-8111-111111111111');

        $createdResponse = $this->actingAs($user)->postJson(route('sync.dtr'), $payload);
        $retryResponse = $this->postJson(route('sync.dtr'), $payload);

        $createdResponse->assertCreated();
        $createdResponse->assertJsonPath('status', 'synced');
        $createdResponse->assertJsonPath('record.total_minutes', 480);
        $retryResponse->assertOk();
        $retryResponse->assertJsonPath('status', 'synced');
        $retryResponse->assertJsonPath('message', 'This DTR draft was already synced.');

        $this->assertDatabaseCount('dtr_entries', 1);
    }

    public function test_same_idempotency_key_with_changed_dtr_returns_409_conflict(): void
    {
        $user = User::factory()->create();
        $payload = $this->dtrPayload('22222222-2222-4222-8222-222222222222');
        $this->actingAs($user)->postJson(route('sync.dtr'), $payload)->assertCreated();

        $response = $this->postJson(route('sync.dtr'), [
            ...$payload,
            'notes' => 'Changed after first sync.',
        ]);

        $response->assertConflict();
        $response->assertJsonPath('status', 'conflict');
        $response->assertJsonPath('conflict_type', 'idempotency_key');

        $this->assertDatabaseCount('dtr_entries', 1);
    }

    public function test_different_key_for_existing_work_date_returns_409_conflict(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)
            ->postJson(route('sync.dtr'), $this->dtrPayload('33333333-3333-4333-8333-333333333333'))
            ->assertCreated();

        $response = $this->postJson(
            route('sync.dtr'),
            $this->dtrPayload('44444444-4444-4444-8444-444444444444'),
        );

        $response->assertConflict();
        $response->assertJsonPath('status', 'conflict');
        $response->assertJsonPath('conflict_type', 'work_date');

        $this->assertDatabaseCount('dtr_entries', 1);
    }

    public function test_same_activity_draft_retry_returns_existing_record_without_duplicate(): void
    {
        $user = User::factory()->create();
        $payload = [
            'activity_date' => '2026-10-16',
            'tasks_completed' => 'Configured the project and documented progress.',
            'skills_tools' => 'Laravel, Git',
            'lessons_learned' => 'Validated work before syncing.',
            'challenges_notes' => null,
            'idempotency_key' => '55555555-5555-4555-8555-555555555555',
        ];

        $createdResponse = $this->actingAs($user)->postJson(route('sync.activities'), $payload);
        $retryResponse = $this->postJson(route('sync.activities'), $payload);

        $createdResponse->assertCreated();
        $createdResponse->assertJsonPath('status', 'synced');
        $retryResponse->assertOk();
        $retryResponse->assertJsonPath('message', 'This activity draft was already synced.');

        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_offline_dtr_and_activity_drafts_reject_future_dates(): void
    {
        $this->travelTo('2026-11-01 09:00:00');
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('sync.dtr'), [
            ...$this->dtrPayload('88888888-8888-4888-8888-888888888888'),
            'work_date' => '2026-11-02',
        ])->assertUnprocessable()->assertJsonValidationErrors('work_date');
        $this->postJson(route('sync.activities'), [
            'activity_date' => '2026-11-02',
            'tasks_completed' => 'This future activity must remain invalid.',
            'idempotency_key' => '99999999-9999-4999-8999-999999999999',
        ])->assertUnprocessable()->assertJsonValidationErrors('activity_date');
        StudentProfile::factory()->for($user)->create([
            'ojt_start_date' => '2026-10-15',
            'ojt_end_date' => '2026-10-31',
        ]);
        $this->postJson(route('sync.activities'), [
            'activity_date' => '2026-10-14',
            'tasks_completed' => 'This pre-placement activity must remain invalid.',
            'idempotency_key' => 'aaaaaaaa-aaaa-4aaa-8aaa-aaaaaaaaaaaa',
        ])->assertUnprocessable()->assertJsonValidationErrors('activity_date');

        $this->assertDatabaseCount('dtr_entries', 0);
        $this->assertDatabaseCount('activity_logs', 0);
    }

    /**
     * @return array<string, string>
     */
    private function dtrPayload(string $idempotencyKey): array
    {
        return [
            'work_date' => '2026-10-16',
            'time_in' => '08:15',
            'break_start' => '12:00',
            'break_end' => '12:45',
            'time_out' => '17:00',
            'notes' => 'Offline DTR draft.',
            'idempotency_key' => $idempotencyKey,
        ];
    }
}
