<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ProfileControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_profile_update_rejects_student_id_outside_required_format(): void
    {
        $student = User::factory()->create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'student_id' => '2310060-1',
            'program' => 'BSIT',
            'section' => '4A',
        ]);

        $response = $this->actingAs($student)->patch(route('profile.update'), [
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'student_id' => '2310060-3',
            'program' => 'BSIT',
            'section' => '4A',
        ]);

        $response->assertRedirect();
        $response->assertSessionHasErrors([
            'student_id' => 'The Student ID must use the format 2310060-1 or 2310060-2.',
        ]);

        $this->assertDatabaseHas('users', [
            'id' => $student->id,
            'student_id' => '2310060-1',
        ]);
    }

    public function test_profile_dates_cannot_exclude_existing_dtr_or_activity_records(): void
    {
        $student = User::factory()->create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'student_id' => '2310060-1',
            'program' => 'BSIT',
            'section' => '4A',
        ]);
        DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-10']);
        ActivityLog::factory()->for($student)->create(['activity_date' => '2026-10-20']);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'student_id' => $student->student_id,
            'program' => $student->program,
            'section' => $student->section,
            'ojt_start_date' => '2026-10-11',
            'ojt_end_date' => '2026-10-19',
        ])->assertSessionHasErrors(['ojt_start_date', 'ojt_end_date']);

        $this->assertDatabaseMissing('student_profiles', ['user_id' => $student->id]);
    }

    public function test_omitted_profile_dates_use_active_term_boundaries_when_validating_existing_records(): void
    {
        $student = User::factory()->create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'student_id' => '2310060-1',
            'program' => 'BSIT',
            'section' => '4A',
        ]);
        AcademicTerm::factory()->create([
            'starts_on' => '2026-10-11',
            'ends_on' => '2026-10-19',
            'is_active' => true,
        ]);
        DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-10']);
        ActivityLog::factory()->for($student)->create(['activity_date' => '2026-10-20']);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'student_id' => $student->student_id,
            'program' => $student->program,
            'section' => $student->section,
        ])->assertSessionHasErrors(['ojt_start_date', 'ojt_end_date']);

        $this->assertDatabaseMissing('student_profiles', ['user_id' => $student->id]);
    }

    public function test_profile_dates_must_use_iso_format(): void
    {
        $student = User::factory()->create([
            'name' => 'Existing Student',
            'email' => 'existing@example.com',
            'student_id' => '2310060-1',
            'program' => 'BSIT',
            'section' => '4A',
        ]);

        $this->actingAs($student)->patch(route('profile.update'), [
            'name' => $student->name,
            'email' => $student->email,
            'student_id' => $student->student_id,
            'program' => $student->program,
            'section' => $student->section,
            'ojt_start_date' => '10/01/2026',
            'ojt_end_date' => '10/31/2026',
        ])->assertSessionHasErrors(['ojt_start_date', 'ojt_end_date']);
    }
}
