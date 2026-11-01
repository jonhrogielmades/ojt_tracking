<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\DtrEditHistory;
use App\Models\DtrEntry;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DtrControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_valid_dtr_uses_server_calculated_minutes_and_ignores_forged_total(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dtr.store'), [
            ...$this->validDtrPayload(),
            'total_minutes' => 9999,
        ]);

        $response->assertRedirectToRoute('dtr.index');
        $response->assertSessionHas('success');

        $entry = DtrEntry::query()->whereBelongsTo($user)->sole();
        $this->assertSame('2026-10-15', $entry->work_date->toDateString());
        $this->assertSame(480, $entry->total_minutes);
    }

    public function test_dtr_rejects_unpaired_break_times(): void
    {
        $user = User::factory()->create();
        $payload = $this->validDtrPayload();
        unset($payload['break_end']);

        $response = $this->actingAs($user)->post(route('dtr.store'), $payload);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_end');

        $this->assertDatabaseCount('dtr_entries', 0);
    }

    public function test_dtr_rejects_break_outside_work_period(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post(route('dtr.store'), [
            ...$this->validDtrPayload(),
            'break_start' => '07:30',
            'break_end' => '08:00',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors('break_start');

        $this->assertDatabaseCount('dtr_entries', 0);
    }

    public function test_dtr_rejects_duplicate_work_date_for_same_student(): void
    {
        $user = User::factory()->create();
        DtrEntry::factory()->for($user)->create(['work_date' => '2026-10-15']);

        $response = $this->actingAs($user)->post(route('dtr.store'), $this->validDtrPayload());

        $response->assertRedirect();
        $response->assertSessionHasErrors('work_date');

        $this->assertDatabaseCount('dtr_entries', 1);
    }

    public function test_dtr_rejects_future_dates_and_dates_outside_the_placement(): void
    {
        $this->travelTo('2026-11-01 09:00:00');
        $user = User::factory()->create();
        StudentProfile::factory()->for($user)->create([
            'ojt_start_date' => '2026-10-01',
            'ojt_end_date' => '2026-10-31',
        ]);

        $this->actingAs($user)->post(route('dtr.store'), [
            ...$this->validDtrPayload(),
            'work_date' => '2026-11-02',
        ])->assertSessionHasErrors('work_date');
        $this->actingAs($user)->post(route('dtr.store'), [
            ...$this->validDtrPayload(),
            'work_date' => '2026-09-30',
        ])->assertSessionHasErrors('work_date');

        $this->assertDatabaseCount('dtr_entries', 0);
    }

    public function test_updating_dtr_records_field_level_edit_history(): void
    {
        $user = User::factory()->create();
        $dtr = DtrEntry::factory()->for($user)->create([
            'work_date' => '2026-10-15',
            'notes' => 'Initial note',
        ]);

        $response = $this->actingAs($user)->patch(route('dtr.update', $dtr), [
            ...$this->validDtrPayload(),
            'time_out' => '18:00',
            'notes' => 'Extended shift',
        ]);

        $response->assertRedirectToRoute('dtr.index');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('dtr_entries', [
            'id' => $dtr->id,
            'total_minutes' => 540,
            'notes' => 'Extended shift',
        ]);
        $this->assertDatabaseHas('dtr_edit_histories', [
            'dtr_entry_id' => $dtr->id,
            'user_id' => $user->id,
        ]);

        $history = DtrEditHistory::query()->whereBelongsTo($dtr)->sole();
        $this->assertSame(['old' => '17:00', 'new' => '18:00'], $history->changes['time_out']);
        $this->assertSame(['old' => 480, 'new' => 540], $history->changes['total_minutes']);
    }

    public function test_editing_another_students_dtr_returns_404(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $otherDtr = DtrEntry::factory()->for($otherStudent)->create();

        $response = $this->actingAs($student)->get(route('dtr.edit', $otherDtr));

        $response->assertNotFound();
    }

    /**
     * @return array<string, string>
     */
    private function validDtrPayload(): array
    {
        return [
            'work_date' => '2026-10-15',
            'time_in' => '08:15',
            'break_start' => '12:00',
            'break_end' => '12:45',
            'time_out' => '17:00',
            'notes' => 'Completed assigned work.',
        ];
    }
}
