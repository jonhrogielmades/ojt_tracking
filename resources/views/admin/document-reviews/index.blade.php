@extends('layouts.admin')

@section('title', 'Document review')
@section('eyebrow', 'Approval workflow')
@section('page-title', 'Student document review')

@section('content')
    <section class="card card-body">
        <form method="GET" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-[14rem_1fr_auto] lg:items-end"><div><label for="status" class="label">Review status</label><select id="status" name="status" class="input"><option value="">All statuses</option>@foreach (['pending', 'approved', 'rejected'] as $status)<option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ str($status)->title() }}</option>@endforeach</select></div><div><label for="program" class="label">Program</label><select id="program" name="program" class="input"><option value="">All programs</option>@foreach ($programs as $program)<option value="{{ $program }}" @selected(($filters['program'] ?? '') === $program)>{{ $program }}</option>@endforeach</select></div><button type="submit" class="btn-secondary">Apply filters</button></form>
    </section>
    <section class="mt-6 space-y-4">
        @forelse ($documents as $document)
            <article class="card card-body"><div class="flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between"><div class="min-w-0"><div class="flex flex-wrap items-center gap-2"><h2 class="font-bold text-slate-950">{{ $document->title }}</h2><span @class(['status-pill', 'bg-amber-100 text-amber-800' => $document->review_status === 'pending', 'bg-emerald-100 text-emerald-800' => $document->review_status === 'approved', 'bg-rose-100 text-rose-800' => $document->review_status === 'rejected'])>{{ str($document->review_status)->title() }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $document->user?->name }} · {{ $document->user?->student_id }} · {{ $document->user?->program }} / {{ $document->user?->section }}</p><p class="mt-2 text-xs text-slate-500">{{ $document->requirement?->name ?? $document->category ?? 'General document' }} · {{ $document->original_name }} · Uploaded {{ $document->created_at?->format('M j, Y g:i A') }}</p>@if ($document->review_notes)<p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600"><span class="font-bold">Current feedback:</span> {{ $document->review_notes }}</p>@endif</div><a href="{{ route('admin.document_reviews.download', $document) }}" class="btn-secondary shrink-0">Download privately</a></div>
                <form method="POST" action="{{ route('admin.document_reviews.update', $document) }}" class="mt-5 grid gap-4 border-t border-slate-200 pt-5 sm:grid-cols-[12rem_1fr_auto] sm:items-end">@csrf @method('PATCH')<div><label for="review_status_{{ $document->id }}" class="label">Decision</label><select id="review_status_{{ $document->id }}" name="review_status" class="input"><option value="approved">Approve</option><option value="rejected">Reject</option></select></div><div><label for="review_notes_{{ $document->id }}" class="label">Feedback <span class="font-normal text-slate-400">(required when rejected)</span></label><input id="review_notes_{{ $document->id }}" name="review_notes" class="input" value="{{ old('review_notes', $document->review_notes) }}" placeholder="Clear, actionable feedback"></div><button type="submit" class="btn-primary">Save review</button></form>
            </article>
        @empty
            <div class="card card-body"><x-empty-state title="No documents in this view" message="Change the filters or wait for students to upload checklist documents." /></div>
        @endforelse
        @if ($documents->hasPages())<div class="card px-5 py-4">{{ $documents->links() }}</div>@endif
    </section>
@endsection
