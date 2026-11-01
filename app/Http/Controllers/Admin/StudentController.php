<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreStudentRequest;
use App\Http\Requests\Admin\UpdateStudentRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\View\View;

class StudentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('viewAny', User::class);
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'in:active,inactive,archived'],
        ]);
        $students = User::query()
            ->when(
                ($filters['status'] ?? null) === 'archived',
                fn (Builder $query): Builder => $query->onlyTrashed(),
            )
            ->students()
            ->withCount(['dtrEntries', 'activityLogs', 'studentDocuments'])
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('student_id', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('program', 'like', "%{$search}%")
                        ->orWhere('section', 'like', "%{$search}%");
                });
            })
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->when(
                ($filters['status'] ?? null) === 'inactive',
                fn (Builder $query): Builder => $query->where('is_active', false),
            )
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('admin.students.index', compact('students', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('create', User::class);

        return view('admin.students.create', ['student' => null]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreStudentRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $data = $request->safe()->only([
            'name',
            'email',
            'student_id',
            'program',
            'section',
            'password',
        ]);
        $student = User::query()->create([
            ...$data,
            'offline_storage_key' => (string) Str::uuid(),
            'role' => UserRole::Student,
            'is_active' => $request->boolean('is_active'),
        ]);
        $auditLogger->record('student.created', $request->user(), $student, newValues: $student->only([
            'name',
            'email',
            'student_id',
            'program',
            'section',
            'role',
            'is_active',
        ]));

        return to_route('admin.students.show', $student)
            ->with('status', 'Student account created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $student): View
    {
        $this->ensureStudent($student);
        Gate::authorize('view', $student);
        $student->load('studentProfile')->loadCount(['dtrEntries', 'activityLogs', 'studentDocuments']);
        $completedMinutes = (int) $student->dtrEntries()->sum('total_minutes');
        $requiredMinutes = (int) ($student->studentProfile?->required_minutes ?? 0);
        $recentDtrEntries = $student->dtrEntries()
            ->orderByDesc('work_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();
        $recentActivities = $student->activityLogs()
            ->orderByDesc('activity_date')
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        return view('admin.students.show', compact(
            'student',
            'completedMinutes',
            'requiredMinutes',
            'recentDtrEntries',
            'recentActivities',
        ));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $student): View
    {
        $this->ensureStudent($student);
        Gate::authorize('update', $student);

        return view('admin.students.edit', compact('student'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateStudentRequest $request,
        User $student,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $this->ensureStudent($student);
        $oldValues = $student->only(['name', 'email', 'student_id', 'program', 'section', 'is_active']);
        $data = $request->safe()->only([
            'name',
            'email',
            'student_id',
            'program',
            'section',
            'password',
        ]);

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        $student->update([
            ...$data,
            'is_active' => $request->boolean('is_active'),
        ]);
        $auditLogger->record('student.updated', $request->user(), $student, $oldValues, $student->only([
            'name',
            'email',
            'student_id',
            'program',
            'section',
            'is_active',
        ]));

        return to_route('admin.students.show', $student)
            ->with('status', 'Student account updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $student, AuditLogger $auditLogger): RedirectResponse
    {
        $this->ensureStudent($student);
        Gate::authorize('delete', $student);
        $student->delete();
        $auditLogger->record('student.archived', request()->user(), $student);

        return to_route('admin.students.index')->with('status', 'Student account archived. Records and files were preserved.');
    }

    private function ensureStudent(User $student): void
    {
        abort_unless($student->isStudent(), 404);
    }
}
