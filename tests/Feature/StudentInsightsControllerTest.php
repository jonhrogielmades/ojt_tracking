<?php

namespace Tests\Feature;

use App\Models\AcademicTerm;
use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\StudentProfile;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentInsightsControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_calendar_combines_dtr_activity_and_academic_holidays(): void
    {
        $student = User::factory()->create();
        DtrEntry::factory()->for($student)->create(['work_date' => '2026-10-27']);
        ActivityLog::factory()->for($student)->create([
            'activity_date' => '2026-10-27',
            'tasks_completed' => 'Built the inventory module',
        ]);
        AcademicTerm::factory()->create([
            'is_active' => true,
            'holidays' => [['date' => '2026-10-30', 'name' => 'Campus Foundation Day']],
        ]);

        $this->actingAs($student)->get(route('student.calendar', ['month' => '2026-10']))
            ->assertOk()
            ->assertSee('Campus Foundation Day')
            ->assertSee('Built the inventory module')
            ->assertSee('8.0h');
    }

    public function test_student_can_update_weekly_target_and_view_forecast(): void
    {
        $student = User::factory()->create();
        StudentProfile::factory()->for($student)->create(['required_minutes' => 6000]);
        DtrEntry::factory()->for($student)->create(['total_minutes' => 600]);

        $this->actingAs($student)->patch(route('student.progress.update'), [
            'weekly_target_hours' => 25,
        ])->assertRedirectToRoute('student.progress');

        $this->assertDatabaseHas('student_profiles', [
            'user_id' => $student->id,
            'weekly_target_minutes' => 1500,
        ]);
        $this->actingAs($student)->get(route('student.progress'))
            ->assertOk()
            ->assertSee('Completion forecast')
            ->assertSee('25.0 hours');
    }

    public function test_portfolio_extracts_skill_frequency_from_owned_activity_logs(): void
    {
        $student = User::factory()->create();
        ActivityLog::factory()->for($student)->create([
            'tasks_completed' => 'Automated a deployment',
            'skills_tools' => 'Laravel, Git, Laravel',
        ]);
        ActivityLog::factory()->create(['skills_tools' => 'Private tool']);

        $this->actingAs($student)->get(route('student.portfolio'))
            ->assertOk()
            ->assertSee('Laravel')
            ->assertSee('×2')
            ->assertSee('Automated a deployment')
            ->assertDontSee('Private tool');
    }
}
