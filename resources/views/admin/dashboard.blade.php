@extends('layouts.admin')

@section('title', 'Admin overview')
@section('eyebrow', now()->format('l, F j'))
@section('page-title', 'Administration overview')

@section('content')
    @php
        $formatHours = static fn (int|float|null $minutes): string => number_format(max(0, (float) $minutes) / 60, 1).' h';
    @endphp

    <section aria-label="Account overview" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card">
            <p class="text-sm font-semibold text-slate-500">Student accounts</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ number_format($studentCount) }}</p>
            <p class="mt-2 text-xs text-slate-500">All registered students</p>
        </article>
        <article class="stat-card">
            <div class="absolute -right-7 -top-7 h-24 w-24 rounded-full bg-emerald-100"></div>
            <p class="relative text-sm font-semibold text-slate-500">Active students</p>
            <p class="relative mt-3 text-3xl font-bold tracking-tight text-emerald-700">{{ number_format($activeStudentCount) }}</p>
            <p class="relative mt-2 text-xs text-slate-500">Can access the student workspace</p>
        </article>
        <article class="stat-card">
            <p class="text-sm font-semibold text-slate-500">Inactive students</p>
            <p class="mt-3 text-3xl font-bold tracking-tight text-amber-700">{{ number_format($inactiveStudentCount) }}</p>
            <p class="mt-2 text-xs text-slate-500">Access temporarily disabled</p>
        </article>
        <article class="stat-card bg-slate-950 text-white">
            <p class="text-sm font-semibold text-slate-400">Recorded training</p>
            <p class="mt-3 text-3xl font-bold tracking-tight">{{ $formatHours($completedMinutes) }}</p>
            <p class="mt-2 text-xs text-slate-400">Combined official DTR hours</p>
        </article>
    </section>

    <section class="mt-6 grid gap-4 md:grid-cols-2" aria-label="Admin actions">
        @can('create', App\Models\User::class)
            <a href="{{ route('admin.students.create') }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:border-teal-300">
                <div><p class="eyebrow">Account management</p><p class="mt-1 font-bold text-slate-950">Register a student</p><p class="mt-1 text-sm text-slate-500">Create credentials with the required Student ID format.</p></div>
                <span class="text-2xl text-teal-600 transition group-hover:translate-x-1">→</span>
            </a>
        @endcan
        <a href="{{ route('admin.students.index', ['status' => 'inactive']) }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:border-amber-300">
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-amber-700">Access review</p><p class="mt-1 font-bold text-slate-950">Review inactive accounts</p><p class="mt-1 text-sm text-slate-500">Restore access when a student is ready to continue.</p></div>
            <span class="text-2xl text-amber-600 transition group-hover:translate-x-1">→</span>
        </a>
    </section>

    <section class="mt-6 card overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6">
            <div><h2 class="section-title">Recently registered students</h2><p class="section-copy">Newest student accounts in the system</p></div>
            <a href="{{ route('admin.students.index') }}" class="text-sm font-bold text-teal-700 hover:text-teal-800">View all</a>
        </div>
        @if ($recentStudents->isEmpty())
            <div class="p-5 sm:p-6">
                @can('create', App\Models\User::class)
                    <x-empty-state title="No students yet" message="Create the first student account or let a student register from the public student sign-up page." :action="route('admin.students.create')" action-label="Add student" />
                @else
                    <x-empty-state title="No students yet" message="No student accounts have been registered." />
                @endcan
            </div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Student</th><th>Program / section</th><th>Status</th><th>Registered</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($recentStudents as $student)
                            <tr>
                                <td><p class="font-bold text-slate-950">{{ $student->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $student->student_id }}</p></td>
                                <td><p class="font-semibold text-slate-800">{{ $student->program }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $student->section }}</p></td>
                                <td><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $student->is_active, 'bg-amber-100 text-amber-800' => ! $student->is_active])>{{ $student->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="whitespace-nowrap">{{ $student->created_at?->format('M j, Y') }}</td>
                                <td class="text-right"><a href="{{ route('admin.students.show', $student) }}" class="text-sm font-bold text-teal-700 hover:text-teal-800">Open</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </section>
@endsection
