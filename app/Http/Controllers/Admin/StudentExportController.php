<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StudentExportController extends Controller
{
    public function __invoke(Request $request): StreamedResponse
    {
        Gate::authorize('viewAny', User::class);
        $validated = $request->validate(['type' => ['nullable', 'in:students,progress']]);
        $type = $validated['type'] ?? 'students';

        return response()->streamDownload(function () use ($type): void {
            $stream = fopen('php://output', 'wb');
            if ($stream === false) {
                return;
            }

            if ($type === 'progress') {
                fputcsv($stream, ['Student ID', 'Name', 'Email', 'Program', 'Section', 'Company', 'Completed Hours', 'Required Hours', 'Remaining Hours', 'Account Status']);
                User::query()
                    ->students()
                    ->with('studentProfile:id,user_id,company_name,required_minutes')
                    ->withSum('dtrEntries', 'total_minutes')
                    ->orderBy('name')
                    ->orderBy('id')
                    ->lazy(200)
                    ->each(function (User $student) use ($stream): void {
                        $completedMinutes = (int) ($student->dtr_entries_sum_total_minutes ?? 0);
                        $requiredMinutes = (int) ($student->studentProfile?->required_minutes ?? 0);
                        fputcsv($stream, [
                            $this->csvCell($student->student_id),
                            $this->csvCell($student->name),
                            $this->csvCell($student->email),
                            $this->csvCell($student->program),
                            $this->csvCell($student->section),
                            $this->csvCell($student->studentProfile?->company_name),
                            number_format($completedMinutes / 60, 2, '.', ''),
                            number_format($requiredMinutes / 60, 2, '.', ''),
                            number_format(max(0, $requiredMinutes - $completedMinutes) / 60, 2, '.', ''),
                            $student->is_active ? 'Active' : 'Inactive',
                        ]);
                    });
            } else {
                fputcsv($stream, ['Student ID', 'Name', 'Email', 'Program', 'Section', 'Account Status', 'Registered At']);
                User::query()
                    ->students()
                    ->orderBy('name')
                    ->orderBy('id')
                    ->cursor()
                    ->each(function (User $student) use ($stream): void {
                        fputcsv($stream, [
                            $this->csvCell($student->student_id),
                            $this->csvCell($student->name),
                            $this->csvCell($student->email),
                            $this->csvCell($student->program),
                            $this->csvCell($student->section),
                            $student->is_active ? 'Active' : 'Inactive',
                            $student->created_at?->toIso8601String(),
                        ]);
                    });
            }

            fclose($stream);
        }, 'ojt-'.$type.'-'.now()->format('Y-m-d').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function csvCell(mixed $value): string
    {
        $cell = str_replace("\0", '', (string) $value);

        return preg_match('/^\s*[=+\-@]/u', $cell) === 1 ? "'".$cell : $cell;
    }
}
