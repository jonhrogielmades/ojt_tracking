@extends('layouts.app')

@section('title', 'Edit activity')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Edit activity log')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex justify-end"><a href="{{ route('activities.index') }}" class="btn-ghost">Back</a></div>
        <form method="POST" action="{{ route('activities.update', $activity) }}" enctype="multipart/form-data" class="card card-body space-y-6">
            @csrf @method('PUT')
            <div><label for="activity_date" class="label">Activity date</label><input id="activity_date" name="activity_date" type="date" value="{{ old('activity_date', $activity->activity_date?->format('Y-m-d')) }}" required @class(['input', 'input-error' => $errors->has('activity_date')])><x-field-error name="activity_date" /></div>
            <div><label for="tasks_completed" class="label">Tasks completed</label><textarea id="tasks_completed" name="tasks_completed" rows="5" required @class(['input', 'input-error' => $errors->has('tasks_completed')])>{{ old('tasks_completed', $activity->tasks_completed) }}</textarea><x-field-error name="tasks_completed" /></div>
            <div><label for="skills_tools" class="label">Skills or tools used</label><textarea id="skills_tools" name="skills_tools" rows="3" @class(['input', 'input-error' => $errors->has('skills_tools')])>{{ old('skills_tools', $activity->skills_tools) }}</textarea><x-field-error name="skills_tools" /></div>
            <div><label for="lessons_learned" class="label">Lessons learned</label><textarea id="lessons_learned" name="lessons_learned" rows="4" @class(['input', 'input-error' => $errors->has('lessons_learned')])>{{ old('lessons_learned', $activity->lessons_learned) }}</textarea><x-field-error name="lessons_learned" /></div>
            <div><label for="challenges_notes" class="label">Challenges or notes</label><textarea id="challenges_notes" name="challenges_notes" rows="3" @class(['input', 'input-error' => $errors->has('challenges_notes')])>{{ old('challenges_notes', $activity->challenges_notes) }}</textarea><x-field-error name="challenges_notes" /></div>
            <div><label for="attachment" class="label">Replace attachment <span class="font-normal text-slate-400">(optional)</span></label>@if ($activity->attachment_path)<p class="mb-2 text-sm text-slate-600">Current: <a href="{{ route('activities.attachment', $activity) }}" class="font-bold text-teal-700">{{ $activity->attachment_original_name ?? 'Download attachment' }}</a></p>@endif<input id="attachment" name="attachment" type="file" class="input file:mr-3 file:rounded-lg file:border-0 file:bg-slate-100 file:px-3 file:py-1.5 file:text-sm file:font-bold" accept=".pdf,.jpg,.jpeg,.png,.doc,.docx">@if ($activity->attachment_path)<label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-semibold text-rose-700"><input type="checkbox" name="remove_attachment" value="1" class="h-4 w-4 rounded border-slate-300 text-rose-600 focus:ring-rose-500"> Remove the current attachment</label>@endif<x-field-error name="attachment" /></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('activities.index') }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Update activity</button></div>
        </form>
    </div>
@endsection
