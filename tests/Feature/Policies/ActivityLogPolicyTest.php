<?php

namespace Tests\Feature\Policies;

use App\Models\ActivityLog;
use App\Models\User;
use App\Policies\ActivityLogPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ActivityLogPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_student_can_use_collection_abilities_and_manage_owned_activity(): void
    {
        $student = User::factory()->create();
        $activity = ActivityLog::factory()->for($student)->create();
        $policy = new ActivityLogPolicy;

        $this->assertTrue($policy->viewAny($student));
        $this->assertTrue($policy->create($student));
        $this->assertTrue($policy->view($student, $activity));
        $this->assertTrue($policy->update($student, $activity));
        $this->assertTrue($policy->delete($student, $activity));
    }

    public function test_active_student_cannot_manage_another_students_activity(): void
    {
        $student = User::factory()->create();
        $activity = ActivityLog::factory()->for(User::factory()->create())->create();
        $policy = new ActivityLogPolicy;

        $this->assertFalse($policy->view($student, $activity));
        $this->assertFalse($policy->update($student, $activity));
        $this->assertFalse($policy->delete($student, $activity));
    }

    public function test_admin_and_inactive_student_cannot_use_student_activity_abilities(): void
    {
        $owner = User::factory()->create();
        $activity = ActivityLog::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create();
        $inactiveStudent = User::factory()->inactive()->create();
        $policy = new ActivityLogPolicy;

        foreach ([$admin, $inactiveStudent] as $user) {
            $this->assertFalse($policy->viewAny($user));
            $this->assertFalse($policy->create($user));
            $this->assertFalse($policy->view($user, $activity));
            $this->assertFalse($policy->update($user, $activity));
            $this->assertFalse($policy->delete($user, $activity));
        }
    }
}
