@extends('layouts.admin')

@section('title', 'Student accounts')
@section('eyebrow', 'Account management')
@section('page-title', 'Student accounts')

@section('content')
    <section class="card card-body">
        <form method="GET" action="{{ route('admin.students.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_13rem_auto] lg:items-end">
            <div>
                <label for="q" class="label">Search students</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name, Student ID, email, program, or section">
            </div>
            <div>
                <label for="status" class="label">Account status</label>
                <select id="status" name="status" class="input">
                    <option value="">All statuses</option>
                    <option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option>
                    <option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option>
                    <option value="archived" @selected(($filters['status'] ?? '') === 'archived')>Archived</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="btn-primary flex-1 lg:flex-none">Apply filters</button>
                @if (($filters['q'] ?? '') !== '' || ($filters['status'] ?? '') !== '')
                    <a href="{{ route('admin.students.index') }}" class="btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </section>

    <section class="mt-6 card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div><h2 class="section-title">Students</h2><p class="section-copy">{{ number_format($students->total()) }} {{ Illuminate\Support\Str::plural('account', $students->total()) }} found</p></div>
            @can('create', App\Models\User::class)
                <a href="{{ route('admin.students.create') }}" class="btn-primary">Add student</a>
            @endcan
        </div>

        @if ($students->isEmpty())
            <div class="p-5 sm:p-6">
                @can('create', App\Models\User::class)
                    <x-empty-state title="No students found" message="Try a different search or status filter, or create a new student account." :action="route('admin.students.create')" action-label="Add student" />
                @else
                    <x-empty-state title="No students found" message="No student accounts match the current filters." />
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Student</th><th>Program / section</th><th>Records</th><th>Status</th><th>Registered</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($students as $student)
                            <tr>
                                <td><p class="font-bold text-slate-950">{{ $student->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $student->student_id }} · {{ $student->email }}</p></td>
                                <td><p class="font-semibold text-slate-800">{{ $student->program }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $student->section }}</p></td>
                                <td><p class="text-xs font-semibold text-slate-700">{{ $student->dtr_entries_count ?? 0 }} DTR · {{ $student->activity_logs_count ?? 0 }} logs</p><p class="mt-1 text-xs text-slate-500">{{ $student->student_documents_count ?? 0 }} documents</p></td>
                                <td><span @class(['status-pill', 'bg-slate-200 text-slate-800' => $student->trashed(), 'bg-emerald-100 text-emerald-800' => ! $student->trashed() && $student->is_active, 'bg-amber-100 text-amber-800' => ! $student->trashed() && ! $student->is_active])>{{ $student->trashed() ? 'Archived' : ($student->is_active ? 'Active' : 'Inactive') }}</span></td>
                                <td class="whitespace-nowrap">{{ $student->created_at?->format('M j, Y') }}</td>
                                <td>
                                    <div class="flex items-center justify-end gap-3">
                                        @if ($student->trashed())
                                            @can('restore', $student)
                                                <form method="POST" action="{{ route('admin.students.restore', ['studentId' => $student->id]) }}">@csrf @method('PATCH')<button type="submit" class="text-sm font-bold text-teal-700 hover:text-teal-800">Restore</button></form>
                                            @endcan
                                            @can('forceDelete', $student)
                                                <form method="POST" action="{{ route('admin.students.purge', ['studentId' => $student->id]) }}" data-confirm="Permanently delete this archived student, every record, and all private files?">@csrf @method('DELETE')<button type="submit" class="text-sm font-bold text-rose-700 hover:text-rose-800">Purge</button></form>
                                            @endcan
                                        @else
                                            <a href="{{ route('admin.students.show', $student) }}" class="text-sm font-bold text-teal-700 hover:text-teal-800">View</a>
                                            @can('update', $student)<a href="{{ route('admin.students.edit', $student) }}" class="text-sm font-bold text-slate-600 hover:text-slate-950">Edit</a>@endcan
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $students->links() }}</div>
        @endif
    </section>
@endsection
