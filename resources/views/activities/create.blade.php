@extends('layouts.app')

@section('title', 'Add activity')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Add activity log')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex items-center justify-between gap-4"><p class="section-copy mt-0">Text details can be drafted offline. Attachments require an internet connection.</p><a href="{{ route('activities.index') }}" class="btn-ghost">Back</a></div>
        <form method="POST" action="{{ route('activities.store') }}" enctype="multipart/form-data" data-offline-form data-kind="activity" data-sync-url="{{ route('sync.activities') }}" data-success-url="{{ route('activities.index') }}" class="card card-body space-y-6">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) Illuminate\Support\Str::uuid()) }}">
            <div data-form-status hidden class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert" tabindex="-1"></div>
            <div><label for="activity_date" class="label">Activity date</label><input id="activity_date" name="activity_date" type="date" value="{{ old('activity_date', now()->format('Y-m-d')) }}" required @class(['input', 'input-error' => $errors->has('activity_date')])><x-field-error name="activity_date" /></div>
            <div><label for="tasks_completed" class="label">Tasks completed</label><textarea id="tasks_completed" name="tasks_completed" rows="5" required @class(['input', 'input-error' => $errors->has('tasks_completed')]) placeholder="Describe the work you completed and your contribution">{{ old('tasks_completed') }}</textarea><x-field-error name="tasks_completed" /></div>
            <div><label for="skills_tools" class="label">Skills or tools used</label><textarea id="skills_tools" name="skills_tools" rows="3" @class(['input', 'input-error' => $errors->has('skills_tools')]) placeholder="Example: Laravel, Excel, customer communication">{{ old('skills_tools') }}</textarea><x-field-error name="skills_tools" /></div>
            <div><label for="lessons_learned" class="label">Lessons learned</label><textarea id="lessons_learned" name="lessons_learned" rows="4" @class(['input', 'input-error' => $errors->has('lessons_learned')]) placeholder="What did you understand or improve today?">{{ old('lessons_learned') }}</textarea><x-field-error name="lessons_learned" /></div>
            <div><label for="challenges_notes" class="label">Challenges or notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="challenges_notes" name="challenges_notes" rows="3" @class(['input', 'input-error' => $errors->has('challenges_notes')])>{{ old('challenges_notes') }}</textarea><x-field-error name="challenges_notes" /></div>
            <div><label for="attachment" class="label">Attachment <span class="font-normal text-slate-400">(optional, online only)</span></label><input id="attachment" name="attachment" type="file" class="input file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-bold file:text-slate-700" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx"><p class="help-text">PDF, image, or Word document. Maximum 10 MB. Files are stored privately.</p><x-field-error name="attachment" /></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('activities.index') }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Save activity</button></div>
        </form>
    </div>
@endsection
