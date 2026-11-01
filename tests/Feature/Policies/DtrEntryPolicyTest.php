<?php

namespace Tests\Feature\Policies;

use App\Models\DtrEntry;
use App\Models\User;
use App\Policies\DtrEntryPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DtrEntryPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_student_can_use_collection_abilities_and_manage_owned_entry(): void
    {
        $student = User::factory()->create();
        $entry = DtrEntry::factory()->for($student)->create();
        $policy = new DtrEntryPolicy;

        $this->assertTrue($policy->viewAny($student));
        $this->assertTrue($policy->create($student));
        $this->assertTrue($policy->view($student, $entry));
        $this->assertTrue($policy->update($student, $entry));
        $this->assertTrue($policy->delete($student, $entry));
    }

    public function test_active_student_cannot_manage_another_students_entry(): void
    {
        $student = User::factory()->create();
        $entry = DtrEntry::factory()->for(User::factory()->create())->create();
        $policy = new DtrEntryPolicy;

        $this->assertFalse($policy->view($student, $entry));
        $this->assertFalse($policy->update($student, $entry));
        $this->assertFalse($policy->delete($student, $entry));
    }

    public function test_admin_and_inactive_student_cannot_use_student_entry_abilities(): void
    {
        $owner = User::factory()->create();
        $entry = DtrEntry::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create();
        $inactiveStudent = User::factory()->inactive()->create();
        $policy = new DtrEntryPolicy;

        foreach ([$admin, $inactiveStudent] as $user) {
            $this->assertFalse($policy->viewAny($user));
            $this->assertFalse($policy->create($user));
            $this->assertFalse($policy->view($user, $entry));
            $this->assertFalse($policy->update($user, $entry));
            $this->assertFalse($policy->delete($user, $entry));
        }
    }
}
