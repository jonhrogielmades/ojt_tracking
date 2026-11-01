<?php

namespace App\Http\Controllers\Admin;

use App\Actions\ImportStudentsAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\PreviewStudentImportRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentImportController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function create(Request $request): View
    {
        Gate::authorize('create', User::class);

        return view('admin.students.import', ['preview' => null, 'token' => null]);
    }

    public function preview(PreviewStudentImportRequest $request, ImportStudentsAction $importStudents): View|RedirectResponse
    {
        try {
            $preview = $importStudents->preview($request->file('csv_file'));
        } catch (\RuntimeException $exception) {
            return back()->withErrors(['csv_file' => $exception->getMessage()]);
        }

        $token = Str::random(40);
        $request->session()->put('student_import', [
            'token' => hash('sha256', $token),
            'rows' => collect($preview['rows'])->where('valid', true)->pluck('data')->values()->all(),
        ]);

        return view('admin.students.import', compact('preview', 'token'));
    }

    public function store(Request $request, ImportStudentsAction $importStudents): RedirectResponse
    {
        Gate::authorize('create', User::class);
        $validated = $request->validate(['token' => ['required', 'string', 'size:40']]);
        $pendingImport = $request->session()->pull('student_import');

        abort_unless(
            is_array($pendingImport)
            && isset($pendingImport['token'], $pendingImport['rows'])
            && hash_equals((string) $pendingImport['token'], hash('sha256', $validated['token'])),
            419,
        );

        $result = $importStudents->import($pendingImport['rows']);
        $this->auditLogger->record('students.imported', $request->user(), null, [], [], $result);
        $message = "{$result['created']} student account(s) created; {$result['reset_links_sent']} password setup link(s) sent.";
        if ($result['reset_links_failed'] > 0) {
            $message .= " {$result['reset_links_failed']} reset link(s) could not be sent and should be retried.";
        }

        return to_route('admin.students.index')->with('status', $message);
    }
}
