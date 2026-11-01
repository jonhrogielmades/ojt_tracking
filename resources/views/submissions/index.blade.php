@extends('layouts.app')

@section('title', 'Weekly submissions')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Weekly submissions')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="section-copy mt-0 max-w-2xl">Bundle your daily time records with an editable accomplishment summary, then track review feedback here.</p>
        <a href="{{ route('submissions.create') }}" class="btn-primary">Prepare this week</a>
    </div>

    <section class="grid gap-4 lg:grid-cols-2">
        @forelse ($submissions as $submission)
            @php
                $statusClasses = match ($submission->status) {
                    \App\Enums\WeeklySubmissionStatus::Approved => 'bg-emerald-100 text-emerald-800',
                    \App\Enums\WeeklySubmissionStatus::Rejected => 'bg-rose-100 text-rose-800',
                    \App\Enums\WeeklySubmissionStatus::ChangesRequested => 'bg-amber-100 text-amber-800',
                    \App\Enums\WeeklySubmissionStatus::Submitted => 'bg-blue-100 text-blue-800',
                    default => 'bg-slate-100 text-slate-700',
                };
            @endphp
            <article class="card card-body">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-bold uppercase tracking-[0.16em] text-slate-500">Week of</p>
                        <h2 class="mt-1 text-lg font-bold text-slate-950">{{ $submission->week_start->format('M j, Y') }}</h2>
                    </div>
                    <span class="status-pill {{ $statusClasses }}">{{ $submission->status->label() }}</span>
                </div>
                <p class="mt-4 line-clamp-3 text-sm leading-6 text-slate-600">{{ $submission->summary }}</p>
                <dl class="mt-5 grid grid-cols-2 gap-3 border-t border-slate-200 pt-4 text-sm">
                    <div><dt class="text-slate-500">DTR entries</dt><dd class="font-bold text-slate-900">{{ $submission->dtr_entries_count }}</dd></div>
                    <div><dt class="text-slate-500">Hours</dt><dd class="font-bold text-teal-700">{{ number_format(($submission->dtr_entries_sum_total_minutes ?? 0) / 60, 2) }}</dd></div>
                </dl>
                <div class="mt-5 flex flex-wrap justify-end gap-2">
                    @if ($submission->canBeEdited())
                        <a href="{{ route('submissions.create', ['week' => $submission->week_start->format('Y-m-d')]) }}" class="btn-secondary">Edit week</a>
                    @endif
                    <a href="{{ route('submissions.show', $submission) }}" class="btn-primary">View details</a>
                </div>
            </article>
        @empty
            <div class="card card-body lg:col-span-2">
                <x-empty-state title="No weekly submissions yet" message="Prepare your first week from the DTR entries and activity logs you already recorded." :action="route('submissions.create')" action-label="Prepare a week" />
            </div>
        @endforelse
    </section>

    @if ($submissions->hasPages())
        <div class="mt-6">{{ $submissions->links() }}</div>
    @endif
@endsection
