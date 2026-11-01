<?php

namespace Tests\Feature\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReportControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_dashboard_shows_only_authenticated_students_recent_activity(): void
    {
        $this->withoutVite();
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        ActivityLog::factory()->for($student)->create(['tasks_completed' => 'OWNER TASK VISIBLE']);
        ActivityLog::factory()->for($otherStudent)->create(['tasks_completed' => 'OTHER TASK PRIVATE']);

        $response = $this->actingAs($student)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSeeText('OWNER TASK VISIBLE');
        $response->assertDontSeeText('OTHER TASK PRIVATE');
    }

    public function test_monthly_csv_scopes_records_and_prefixes_formula_cells(): void
    {
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        DtrEntry::factory()->for($student)->create([
            'work_date' => '2026-10-15',
            'notes' => '=1+1',
        ]);
        DtrEntry::factory()->for($otherStudent)->create([
            'work_date' => '2026-10-16',
            'notes' => 'OTHER PRIVATE NOTE',
        ]);

        $response = $this->actingAs($student)->get(route('reports.dtr_csv', ['month' => '2026-10']));
        $content = $response->streamedContent();

        $response->assertOk();
        $response->assertDownload('ojt-dtr-2026-10.csv');
        $this->assertStringContainsString("'=1+1", $content);
        $this->assertStringNotContainsString('OTHER PRIVATE NOTE', $content);
    }

    public function test_weekly_report_returns_404_for_another_students_selected_activity(): void
    {
        $this->withoutVite();
        $student = User::factory()->create();
        $otherStudent = User::factory()->create();
        $otherActivity = ActivityLog::factory()->for($otherStudent)->create([
            'activity_date' => '2026-10-12',
        ]);

        $response = $this->actingAs($student)->get(route('reports.weekly', [
            'week' => '2026-10-12',
            'activity_ids' => [$otherActivity->id],
        ]));

        $response->assertNotFound();
    }
}
