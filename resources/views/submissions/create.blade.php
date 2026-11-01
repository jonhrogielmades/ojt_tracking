@extends('layouts.app')

@section('title', 'Prepare weekly submission')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Prepare weekly submission')

@section('content')
    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
        <div>
            <p class="text-sm font-bold text-slate-950">{{ $weekStart->format('M j') }}–{{ $weekEnd->format('M j, Y') }}</p>
            <p class="section-copy mt-1">The first summary draft comes from this week’s activity logs. Review it before submitting.</p>
        </div>
        <form method="GET" action="{{ route('submissions.create') }}" class="flex items-end gap-2">
            <div><label for="week" class="label">Choose a week</label><input id="week" name="week" type="date" value="{{ $weekStart->format('Y-m-d') }}" class="input"></div>
            <button class="btn-secondary" type="submit">Load</button>
        </form>
    </div>

    @if ($submission && ! $submission->canBeEdited())
        <section class="card card-body">
            <p class="text-sm font-bold text-slate-950">This week is {{ strtolower($submission->status->label()) }} and is locked.</p>
            <p class="mt-2 text-sm text-slate-600">You can edit it again only if a reviewer requests changes.</p>
            <a href="{{ route('submissions.show', $submission) }}" class="btn-primary mt-5">View submission</a>
        </section>
    @else
        @if ($submission?->reviewer_comments)
            <div class="mb-6 rounded-2xl border border-amber-200 bg-amber-50 p-4 text-sm text-amber-900">
                <p class="font-bold">Reviewer feedback</p><p class="mt-1 leading-6">{{ $submission->reviewer_comments }}</p>
            </div>
        @endif

        <form method="POST" action="{{ route('submissions.store') }}" class="space-y-6">
            @csrf
            <input type="hidden" name="week_start" value="{{ $weekStart->format('Y-m-d') }}">

            <section class="card card-body">
                <h2 class="section-title">1. Select DTR entries</h2>
                <p class="section-copy">Only entries dated within this week can be attached.</p>
                <div class="mt-5 grid gap-3 sm:grid-cols-2 xl:grid-cols-3">
                    @forelse ($dtrEntries as $entry)
                        <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 p-4 hover:border-teal-300">
                            <input name="dtr_entry_ids[]" value="{{ $entry->id }}" type="checkbox" class="mt-1 h-4 w-4 rounded border-slate-300 text-teal-700" @checked(in_array($entry->id, old('dtr_entry_ids', $selectedEntryIds)))>
                            <span><span class="block font-bold text-slate-950">{{ $entry->work_date->format('D, M j') }}</span><span class="mt-1 block text-sm text-slate-500">{{ number_format($entry->total_minutes / 60, 2) }} hours</span></span>
                        </label>
                    @empty
                        <div class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600 sm:col-span-2 xl:col-span-3">No DTR entries are available for this week. <a href="{{ route('dtr.create') }}" class="font-bold text-teal-700">Add one first</a>.</div>
                    @endforelse
                </div>
                <x-field-error name="dtr_entry_ids" />
            </section>

            <section class="card card-body">
                <h2 class="section-title">2. Review accomplishment summary</h2>
                <p class="section-copy">Generated from {{ $activities->count() }} activity {{ Str::plural('log', $activities->count()) }}. You can rewrite it.</p>
                <div class="mt-5"><label for="summary" class="label">Weekly summary</label><textarea id="summary" name="summary" rows="10" required @class(['input', 'input-error' => $errors->has('summary')])>{{ old('summary', $generatedSummary) }}</textarea><x-field-error name="summary" /></div>
                <div class="mt-5"><label for="student_notes" class="label">Notes for reviewer <span class="font-normal text-slate-400">(optional)</span></label><textarea id="student_notes" name="student_notes" rows="4" @class(['input', 'input-error' => $errors->has('student_notes')])>{{ old('student_notes', $submission?->student_notes) }}</textarea><x-field-error name="student_notes" /></div>
            </section>

            <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                <a href="{{ route('submissions.index') }}" class="btn-secondary">Cancel</a>
                <button type="submit" name="intent" value="draft" class="btn-secondary">Save draft</button>
                <button type="submit" name="intent" value="submit" class="btn-primary">Submit for review</button>
            </div>
        </form>
    @endif
@endsection
