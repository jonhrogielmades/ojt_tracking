@extends('layouts.admin')

@section('title', $student->name)
@section('eyebrow', 'Student account')
@section('page-title', $student->name)

@section('content')
    @php
        $formatHours = static fn (int|float|null $minutes): string => number_format(max(0, (float) $minutes) / 60, 1).' h';
        $profile = $student->studentProfile;
    @endphp

    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <a href="{{ route('admin.students.index') }}" class="text-sm font-bold text-slate-600 hover:text-slate-950">← Back to student accounts</a>
        @can('update', $student)<div class="flex gap-2"><a href="{{ route('admin.students.edit', $student) }}" class="btn-primary">Edit account</a></div>@endcan
    </div>

    <section class="card card-body">
        <div class="flex flex-col gap-5 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <div class="flex flex-wrap items-center gap-3">
                    <h2 class="text-2xl font-bold tracking-tight text-slate-950">{{ $student->name }}</h2>
                    <span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $student->is_active, 'bg-amber-100 text-amber-800' => ! $student->is_active])>{{ $student->is_active ? 'Active' : 'Inactive' }}</span>
                </div>
                <p class="mt-2 font-mono text-sm font-bold text-teal-700">{{ $student->student_id }}</p>
                <p class="mt-1 text-sm text-slate-500">{{ $student->email }}</p>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm sm:text-right">
                <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Program</p><p class="mt-1 font-semibold text-slate-800">{{ $student->program }}</p></div>
                <div><p class="text-xs font-bold uppercase tracking-wider text-slate-400">Section</p><p class="mt-1 font-semibold text-slate-800">{{ $student->section }}</p></div>
            </div>
        </div>
    </section>

    <section aria-label="Student progress" class="mt-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Required hours</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $formatHours($requiredMinutes) }}</p><p class="mt-2 text-xs text-slate-500">Configured training target</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Completed hours</p><p class="mt-3 text-3xl font-bold tracking-tight text-teal-700">{{ $formatHours($completedMinutes) }}</p><p class="mt-2 text-xs text-slate-500">Official DTR total</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">DTR entries</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($student->dtr_entries_count ?? 0) }}</p><p class="mt-2 text-xs text-slate-500">Recorded workdays</p></article>
        <article class="stat-card bg-slate-950 text-white"><p class="text-sm font-semibold text-slate-400">Activity logs</p><p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format($student->activity_logs_count ?? 0) }}</p><p class="mt-2 text-xs text-slate-400">Accomplishment records</p></article>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="card card-body">
            <p class="eyebrow">Placement</p>
            <div class="mt-2"><h2 class="section-title">OJT assignment</h2><p class="section-copy">Company and training schedule recorded by the student.</p></div>
            <dl class="mt-6 grid gap-4 text-sm sm:grid-cols-2 xl:grid-cols-1">
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Company</dt><dd class="mt-1 font-semibold text-slate-900">{{ $profile?->company_name ?: 'Not provided' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Department</dt><dd class="mt-1 font-semibold text-slate-900">{{ $profile?->department ?: 'Not provided' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Supervisor</dt><dd class="mt-1 font-semibold text-slate-900">{{ $profile?->supervisor_name ?: 'Not provided' }}</dd></div>
                <div><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">OJT dates</dt><dd class="mt-1 font-semibold text-slate-900">@if ($profile?->ojt_start_date || $profile?->ojt_end_date){{ $profile?->ojt_start_date?->format('M j, Y') ?? 'Not set' }} – {{ $profile?->ojt_end_date?->format('M j, Y') ?? 'Not set' }}@else Not provided @endif</dd></div>
                <div class="sm:col-span-2 xl:col-span-1"><dt class="text-xs font-bold uppercase tracking-wider text-slate-400">Company address</dt><dd class="mt-1 leading-6 text-slate-700">{{ $profile?->company_address ?: 'Not provided' }}</dd></div>
            </dl>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Recent attendance</h2><p class="section-copy">Latest official DTR records</p></div>
            @if ($recentDtrEntries->isEmpty())
                <div class="p-5 sm:p-6"><x-empty-state title="No attendance records" message="This student has not submitted a DTR entry yet." /></div>
            @else
                <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Date</th><th>Time in</th><th>Time out</th><th class="text-right">Hours</th></tr></thead><tbody>
                    @foreach ($recentDtrEntries as $entry)
                        <tr><td class="font-semibold text-slate-900">{{ $entry->work_date?->format('M j, Y') ?? $entry->work_date }}</td><td>{{ $entry->time_in ? \Carbon\Carbon::parse($entry->time_in)->format('g:i A') : '—' }}</td><td>{{ $entry->time_out ? \Carbon\Carbon::parse($entry->time_out)->format('g:i A') : '—' }}</td><td class="text-right font-bold text-teal-700">{{ $formatHours($entry->total_minutes) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>
    </div>

    <section class="mt-6 card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Recent activities</h2><p class="section-copy">Latest accomplishment records from this student</p></div>
        @if ($recentActivities->isEmpty())
            <div class="p-5 sm:p-6"><x-empty-state title="No activity logs" message="This student has not submitted an activity log yet." /></div>
        @else
            <div class="grid divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                @foreach ($recentActivities as $activity)
                    <article class="p-5 sm:p-6"><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $activity->activity_date?->format('M j, Y') ?? $activity->activity_date }}</p><p class="mt-2 line-clamp-3 text-sm font-semibold leading-6 text-slate-900">{{ $activity->tasks_completed }}</p>@if ($activity->skills_tools)<p class="mt-2 text-xs text-slate-500">Tools: {{ $activity->skills_tools }}</p>@endif</article>
                @endforeach
            </div>
        @endif
    </section>

    @can('delete', $student)
        <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex sm:items-center sm:justify-between sm:gap-6">
            <div><h2 class="font-bold text-amber-950">Archive student account</h2><p class="mt-1 text-sm leading-6 text-amber-800">The student can no longer sign in, while all OJT records and private files remain available for restoration.</p></div>
            <form method="POST" action="{{ route('admin.students.destroy', $student) }}" class="mt-4 shrink-0 sm:mt-0" data-confirm="Archive this student account? You can restore it from the archived filter.">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn-secondary w-full">Archive student</button>
            </form>
        </section>
    @endcan
@endsection
