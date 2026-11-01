@extends('layouts.admin')

@section('title', 'At-risk students')
@section('eyebrow', 'Early intervention')
@section('page-title', 'At-risk student dashboard')

@section('content')
    <section class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Active student signals</h2><p class="section-copy">Signals combine training-plan completeness, expected hour pace, recent DTR activity, and overdue approved documents.</p></div>
        <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Student</th><th>Placement</th><th class="text-right">Completed</th><th>Risk signals</th><th>Last DTR</th></tr></thead><tbody>
            @foreach ($students as $student)
                <tr><td><p class="font-bold text-slate-950">{{ $student->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $student->student_id }} · {{ $student->program }} / {{ $student->section }}</p></td><td>{{ $student->studentProfile?->company_name ?: 'Not assigned' }}</td><td class="text-right font-bold">{{ number_format($student->completed_minutes / 60, 2) }} h</td><td>@if ($student->risk_reasons === [])<span class="status-pill bg-emerald-100 text-emerald-800">On track</span>@else<ul class="space-y-1 text-sm text-rose-700">@foreach ($student->risk_reasons as $reason)<li>• {{ $reason }}</li>@endforeach</ul>@endif</td><td class="whitespace-nowrap">{{ $student->dtr_entries_max_work_date ? \Carbon\Carbon::parse($student->dtr_entries_max_work_date)->format('M j, Y') : 'Never' }}</td></tr>
            @endforeach
        </tbody></table></div>
        @if ($students->isEmpty())<div class="p-6"><x-empty-state title="No active students" message="At-risk signals will appear after student accounts are activated." /></div>@endif
        @if ($students->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $students->links() }}</div>@endif
    </section>
@endsection
