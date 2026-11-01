<?php

namespace Tests\Feature\Policies;

use App\Models\StudentDocument;
use App\Models\User;
use App\Policies\StudentDocumentPolicy;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class StudentDocumentPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_active_student_can_use_collection_abilities_and_manage_owned_document(): void
    {
        $student = User::factory()->create();
        $document = StudentDocument::factory()->for($student)->create();
        $policy = new StudentDocumentPolicy;

        $this->assertTrue($policy->viewAny($student));
        $this->assertTrue($policy->create($student));
        $this->assertTrue($policy->view($student, $document));
        $this->assertTrue($policy->update($student, $document));
        $this->assertTrue($policy->delete($student, $document));
    }

    public function test_active_student_cannot_manage_another_students_document(): void
    {
        $student = User::factory()->create();
        $document = StudentDocument::factory()->for(User::factory()->create())->create();
        $policy = new StudentDocumentPolicy;

        $this->assertFalse($policy->view($student, $document));
        $this->assertFalse($policy->update($student, $document));
        $this->assertFalse($policy->delete($student, $document));
    }

    public function test_admin_and_inactive_student_cannot_use_student_document_abilities(): void
    {
        $owner = User::factory()->create();
        $document = StudentDocument::factory()->for($owner)->create();
        $admin = User::factory()->admin()->create();
        $inactiveStudent = User::factory()->inactive()->create();
        $policy = new StudentDocumentPolicy;

        foreach ([$admin, $inactiveStudent] as $user) {
            $this->assertFalse($policy->viewAny($user));
            $this->assertFalse($policy->create($user));
            $this->assertFalse($policy->view($user, $document));
            $this->assertFalse($policy->update($user, $document));
            $this->assertFalse($policy->delete($user, $document));
        }
    }
}
