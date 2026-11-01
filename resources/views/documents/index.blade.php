@extends('layouts.app')

@section('title', 'Documents')
@section('eyebrow', 'Requirements & private files')
@section('page-title', 'OJT documents')

@section('content')
    <section class="card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6">
            <h2 class="section-title">Requirement checklist</h2>
            <p class="section-copy">Requirements are targeted to your program. An approved upload is shown first; otherwise, the latest review status appears.</p>
        </div>
        @if ($requirements->isEmpty())
            <div class="p-5 sm:p-6"><x-empty-state title="No active requirements" message="Your coordinator has not published a document checklist for your program." /></div>
        @else
            <div class="grid gap-3 p-5 sm:grid-cols-2 sm:p-6 xl:grid-cols-3">
                @foreach ($requirements as $requirement)
                    @php
                        $submittedDocument = $latestDocumentsByRequirement->get($requirement->id);
                        $status = $submittedDocument?->review_status ?? 'not-submitted';
                    @endphp
                    <article class="rounded-2xl border border-slate-200 bg-slate-50 p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div><p class="font-bold text-slate-950">{{ $requirement->name }}</p><p class="mt-1 text-xs text-slate-500">{{ $requirement->category ?: 'Document requirement' }}</p></div>
                            <span @class([
                                'status-pill whitespace-nowrap',
                                'bg-emerald-100 text-emerald-800' => $status === 'approved',
                                'bg-amber-100 text-amber-800' => $status === 'pending',
                                'bg-rose-100 text-rose-800' => $status === 'rejected',
                                'bg-slate-200 text-slate-700' => $status === 'not-submitted',
                            ])>{{ str($status)->replace('-', ' ')->title() }}</span>
                        </div>
                        @if ($requirement->description)<p class="mt-3 text-sm leading-6 text-slate-600">{{ $requirement->description }}</p>@endif
                        <p class="mt-3 text-xs font-semibold text-slate-500">{{ $requirement->due_date ? 'Due '.$requirement->due_date->format('M j, Y') : 'No due date' }} · {{ $requirement->is_required ? 'Required' : 'Optional' }}</p>
                        @if ($submittedDocument?->review_notes)<p class="mt-3 rounded-xl bg-white px-3 py-2 text-xs leading-5 text-slate-600"><span class="font-bold">Reviewer feedback:</span> {{ $submittedDocument->review_notes }}</p>@endif
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[0.75fr_1.25fr]">
        <section class="card card-body self-start">
            <p class="eyebrow">Online only</p>
            <h2 class="section-title mt-2">Upload a document</h2>
            <p class="section-copy">Files stay in private storage and downloads always require an authenticated session.</p>
            <form method="POST" action="{{ route('documents.store') }}" enctype="multipart/form-data" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="document_requirement_id" class="label">Checklist requirement <span class="font-normal text-slate-400">(optional)</span></label>
                    <select id="document_requirement_id" name="document_requirement_id" @class(['input', 'input-error' => $errors->has('document_requirement_id')])>
                        <option value="">General supporting document</option>
                        @foreach ($requirements as $requirement)
                            <option value="{{ $requirement->id }}" @selected((string) old('document_requirement_id') === (string) $requirement->id)>{{ $requirement->name }}</option>
                        @endforeach
                    </select>
                    <x-field-error name="document_requirement_id" />
                </div>
                <div><label for="title" class="label">Document title</label><input id="title" name="title" value="{{ old('title') }}" required @class(['input', 'input-error' => $errors->has('title')]) placeholder="Example: Midterm evaluation"><x-field-error name="title" /></div>
                <div><label for="category" class="label">Category <span class="font-normal text-slate-400">(optional)</span></label><select id="category" name="category" class="input"><option value="">Select category</option>@foreach (['Signed form', 'Evaluation', 'Certificate', 'School requirement', 'Other'] as $category)<option value="{{ $category }}" @selected(old('category') === $category)>{{ $category }}</option>@endforeach</select><x-field-error name="category" /></div>
                <div><label for="document" class="label">Choose file</label><input id="document" name="document" type="file" required class="input file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-slate-700" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"><p class="help-text">PDF, JPG, PNG, DOC, or DOCX up to 10 MB.</p><x-field-error name="document" /></div>
                <button type="submit" class="btn-primary w-full">Upload for review</button>
            </form>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Your files</h2><p class="section-copy">Approved files are locked to preserve the reviewed record.</p></div>
            @if ($documents->isEmpty())
                <div class="p-5 sm:p-6"><x-empty-state title="No documents uploaded" message="Use the upload form to securely submit your OJT forms and evaluations." /></div>
            @else
                <div class="divide-y divide-slate-100">
                    @foreach ($documents as $document)
                        <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2"><p class="truncate font-bold text-slate-950">{{ $document->title }}</p><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $document->review_status === 'approved', 'bg-amber-100 text-amber-800' => $document->review_status === 'pending', 'bg-rose-100 text-rose-800' => $document->review_status === 'rejected'])>{{ str($document->review_status)->title() }}</span></div>
                                <p class="mt-1 truncate text-xs text-slate-500">{{ $document->requirement?->name ?? $document->category ?? 'General document' }} · {{ $document->original_name }} · {{ number_format(($document->size ?? 0) / 1024, 0) }} KB</p>
                                <p class="mt-1 text-xs text-slate-400">Uploaded {{ $document->created_at?->format('M j, Y') }}{{ $document->reviewed_at ? ' · Reviewed '.$document->reviewed_at->format('M j, Y') : '' }}</p>
                                @if ($document->review_notes)<p class="mt-2 text-sm text-slate-600"><span class="font-bold">Feedback:</span> {{ $document->review_notes }}</p>@endif
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('documents.download', $document) }}" class="btn-secondary">Download</a>
                                @if ($document->review_status !== 'approved')
                                    <form method="POST" action="{{ route('documents.destroy', $document) }}" data-confirm="Permanently delete this document?">@csrf @method('DELETE')<button type="submit" class="btn-danger">Delete</button></form>
                                @endif
                            </div>
                        </article>
                    @endforeach
                </div>
                <div class="border-t border-slate-200 px-5 py-4">{{ $documents->links() }}</div>
            @endif
        </section>
    </div>
@endsection
