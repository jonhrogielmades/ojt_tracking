<?php

namespace App\Actions;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class DeleteStudentAction
{
    public function handle(User $student): void
    {
        abort_unless($student->isStudent(), 404);

        $studentId = $student->getKey();

        DB::transaction(function () use ($student): void {
            $student->forceDelete();
        });

        Storage::disk((string) config('ojt.documents.disk', 'local'))
            ->deleteDirectory("ojt-documents/{$studentId}");
        Storage::disk((string) config('ojt.attachments.disk', 'local'))
            ->deleteDirectory("activity-attachments/{$studentId}");
    }
}
