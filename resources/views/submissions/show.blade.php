@extends('layouts.app')

@section('title', 'Weekly submission')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Weekly submission')

@section('content')
    @php
        $statusClasses = match ($submission->status) {
            \App\Enums\WeeklySubmissionStatus::Approved => 'bg-emerald-100 text-emerald-800',
            \App\Enums\WeeklySubmissionStatus::Rejected => 'bg-rose-100 text-rose-800',
            \App\Enums\WeeklySubmissionStatus::ChangesRequested => 'bg-amber-100 text-amber-800',
            \App\Enums\WeeklySubmissionStatus::Submitted => 'bg-blue-100 text-blue-800',
            default => 'bg-slate-100 text-slate-700',
        };
    @endphp
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="text-sm text-slate-500">Week of</p><h2 class="text-xl font-bold text-slate-950">{{ $submission->week_start->format('M j, Y') }}</h2></div>
        <div class="flex flex-wrap items-center gap-2"><span class="status-pill {{ $statusClasses }}">{{ $submission->status->label() }}</span>@if ($submission->canBeEdited())<a href="{{ route('submissions.create', ['week' => $submission->week_start->format('Y-m-d')]) }}" class="btn-primary">Edit week</a>@endif</div>
    </div>

    @if ($submission->reviewer_comments)
        <section class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-5">
            <p class="text-sm font-bold text-amber-950">Reviewer feedback{{ $submission->reviewer ? ' from '.$submission->reviewer->name : '' }}</p>
            <p class="mt-2 whitespace-pre-line text-sm leading-6 text-amber-900">{{ $submission->reviewer_comments }}</p>
        </section>
    @endif

    <div class="grid gap-6 xl:grid-cols-[minmax(0,1.5fr)_minmax(20rem,1fr)]">
        <section class="card card-body">
            <h2 class="section-title">Accomplishment summary</h2>
            <p class="mt-4 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $submission->summary }}</p>
            @if ($submission->student_notes)<div class="mt-5 rounded-2xl bg-slate-50 p-4"><p class="text-xs font-bold uppercase tracking-wide text-slate-500">Notes for reviewer</p><p class="mt-2 text-sm text-slate-700">{{ $submission->student_notes }}</p></div>@endif
        </section>
        <section class="card overflow-hidden">
            <div class="card-body pb-3"><h2 class="section-title">Attached DTR entries</h2></div>
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Date</th><th class="text-right">Hours</th></tr></thead><tbody>@foreach ($submission->dtrEntries as $entry)<tr><td>{{ $entry->work_date->format('M j, Y') }}</td><td class="text-right font-bold text-teal-700">{{ number_format($entry->total_minutes / 60, 2) }}</td></tr>@endforeach</tbody><tfoot><tr><th>Total</th><th class="text-right">{{ number_format($submission->dtrEntries->sum('total_minutes') / 60, 2) }}</th></tr></tfoot></table></div>
        </section>
    </div>

    @if ($submission->status === \App\Enums\WeeklySubmissionStatus::Approved)
        <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 text-sm text-slate-600">Need to correct an approved DTR? <a href="{{ route('corrections.index') }}" class="font-bold text-teal-700">Submit a correction request</a>.</div>
    @endif
@endsection
