@extends('layouts.admin')

@section('title', 'Approval queue')
@section('eyebrow', 'Pending work')
@section('page-title', 'Approval queue')

@section('content')
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between border-b border-slate-200 px-5 py-4 sm:px-6"><div><h2 class="section-title">Documents</h2><p class="section-copy">{{ $pendingDocuments->count() }} oldest pending upload(s)</p></div><a href="{{ route('admin.document_reviews.index', ['status' => 'pending']) }}" class="btn-secondary">Open reviews</a></div>
            <div class="divide-y divide-slate-100">@forelse ($pendingDocuments as $document)<article class="p-5"><p class="font-bold text-slate-950">{{ $document->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $document->user?->name }} · {{ $document->user?->student_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $document->requirement?->name ?? 'General document' }} · {{ $document->created_at?->diffForHumans() }}</p></article>@empty<div class="p-6"><x-empty-state title="No pending documents" message="New uploads will appear here for review." /></div>@endforelse</div>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Weekly submissions</h2><p class="section-copy">Approve, reject, or request changes from a submitted weekly report.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($weeklySubmissions as $submission)
                    <article class="p-5 sm:p-6"><div><p class="font-bold text-slate-950">{{ $submission->user?->name }} · Week of {{ $submission->week_start->format('M j, Y') }}</p><p class="mt-1 text-xs text-slate-500">{{ $submission->user?->student_id }} · {{ $submission->dtr_entries_count }} DTR entries · {{ number_format(($submission->dtr_entries_sum_total_minutes ?? 0) / 60, 2) }} hours</p><p class="mt-3 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $submission->summary }}</p>@if ($submission->student_notes)<p class="mt-3 rounded-xl bg-slate-50 px-3 py-2 text-sm text-slate-600"><span class="font-bold">Student notes:</span> {{ $submission->student_notes }}</p>@endif</div><form method="POST" action="{{ route('admin.approval_queue.submissions.review', $submission) }}" class="mt-4 grid gap-3 sm:grid-cols-[12rem_1fr_auto] sm:items-end">@csrf @method('PATCH')<div><label for="submission_decision_{{ $submission->id }}" class="label">Decision</label><select id="submission_decision_{{ $submission->id }}" name="decision" class="input"><option value="approved">Approve</option><option value="changes_requested">Request changes</option><option value="rejected">Reject</option></select></div><div><label for="submission_comments_{{ $submission->id }}" class="label">Reviewer comments</label><input id="submission_comments_{{ $submission->id }}" name="reviewer_comments" class="input" placeholder="Required for rejection or changes"></div><button type="submit" class="btn-primary">Submit review</button></form></article>
                @empty
                    <div class="p-6"><x-empty-state title="No submitted weekly reports" message="Newly submitted weeks will appear here for review." /></div>
                @endforelse
            </div>
        </section>
    </div>

    <section class="mt-6 card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">DTR correction requests</h2><p class="section-copy">Approving a request reopens its weekly submission for changes and resubmission.</p></div>
        <div class="grid gap-4 p-5 sm:p-6 xl:grid-cols-2">
            @forelse ($correctionRequests as $correctionRequest)
                <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4"><p class="font-bold text-slate-950">{{ $correctionRequest->user?->name }} · {{ $correctionRequest->dtrEntry?->work_date?->format('M j, Y') }}</p><p class="mt-1 text-xs text-slate-500">{{ $correctionRequest->user?->student_id }} · {{ number_format(($correctionRequest->dtrEntry?->total_minutes ?? 0) / 60, 2) }} logged hours</p><p class="mt-3 text-sm leading-6 text-slate-700"><span class="font-bold">Reason:</span> {{ $correctionRequest->reason }}</p><form method="POST" action="{{ route('admin.approval_queue.corrections.review', $correctionRequest) }}" class="mt-4 space-y-3">@csrf @method('PATCH')<div><label for="correction_decision_{{ $correctionRequest->id }}" class="label">Decision</label><select id="correction_decision_{{ $correctionRequest->id }}" name="decision" class="input"><option value="approved">Approve and reopen week</option><option value="rejected">Reject</option></select></div><div><label for="correction_response_{{ $correctionRequest->id }}" class="label">Response to student</label><textarea id="correction_response_{{ $correctionRequest->id }}" name="reviewer_response" rows="3" required class="input" placeholder="Explain the decision and next step"></textarea></div><button type="submit" class="btn-primary w-full">Submit correction review</button></form></article>
            @empty
                <div class="xl:col-span-2"><x-empty-state title="No pending correction requests" message="Student requests for changes to approved DTR entries will appear here." /></div>
            @endforelse
        </div>
    </section>
@endsection
