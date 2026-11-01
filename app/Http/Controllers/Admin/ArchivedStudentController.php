<?php

namespace App\Http\Controllers\Admin;

use App\Actions\DeleteStudentAction;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;

class ArchivedStudentController extends Controller
{
    public function restore(string $studentId, AuditLogger $auditLogger): RedirectResponse
    {
        $student = $this->archivedStudent($studentId);
        Gate::authorize('restore', $student);
        $student->restore();
        $auditLogger->record('student.restored', request()->user(), $student);

        return to_route('admin.students.show', $student)->with('status', 'Student account restored.');
    }

    public function destroy(
        string $studentId,
        DeleteStudentAction $deleteStudent,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $student = $this->archivedStudent($studentId);
        Gate::authorize('forceDelete', $student);
        $auditLogger->record('student.purged', request()->user(), $student, metadata: [
            'student_id' => $student->student_id,
        ]);
        $deleteStudent->handle($student);

        return to_route('admin.students.index', ['status' => 'archived'])
            ->with('status', 'Student account and private files permanently deleted.');
    }

    private function archivedStudent(string $studentId): User
    {
        return User::query()->onlyTrashed()->students()->findOrFail($studentId);
    }
}
