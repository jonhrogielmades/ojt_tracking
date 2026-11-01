@extends('layouts.app')

@section('title', 'Activity log')
@section('eyebrow', 'Accomplishments')
@section('page-title', 'Activity log')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between"><p class="section-copy mt-0">Keep activities separate from attendance and turn selected entries into a weekly report.</p><a href="{{ route('activities.create') }}" class="btn-primary">Add activity</a></div>

    <form method="GET" action="{{ route('activities.index') }}" class="card mb-6 grid gap-4 p-4 sm:grid-cols-2 lg:grid-cols-[1fr_auto_auto_auto]">
        <div><label for="q" class="sr-only">Search activities</label><input id="q" name="q" type="search" value="{{ $filters['q'] ?? request('q') }}" class="input" placeholder="Search tasks, skills, or lessons"></div>
        <div><label for="from" class="sr-only">From date</label><input id="from" name="from" type="date" value="{{ $filters['from'] ?? request('from') }}" class="input" title="From date"></div>
        <div><label for="to" class="sr-only">To date</label><input id="to" name="to" type="date" value="{{ $filters['to'] ?? request('to') }}" class="input" title="To date"></div>
        <div class="flex gap-2"><button type="submit" class="btn-secondary flex-1">Filter</button>@if (request()->hasAny(['q', 'from', 'to']))<a href="{{ route('activities.index') }}" class="btn-ghost px-2">Clear</a>@endif</div>
    </form>

    @if ($activities->isEmpty())
        <section class="card card-body"><x-empty-state title="No activity logs found" message="Record completed tasks, tools used, lessons learned, and any challenges." :action="route('activities.create')" action-label="Add first activity" /></section>
    @else
        <form id="weekly-report-form" method="GET" action="{{ route('reports.weekly') }}"></form>
            <div class="mb-3 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><p class="text-sm font-semibold text-slate-600">Select entries to include in a weekly accomplishment report.</p><button type="submit" form="weekly-report-form" class="btn-secondary">Build report from selected</button></div>
            <section class="grid gap-4 lg:grid-cols-2">
                @foreach ($activities as $activity)
                    <article class="card card-body flex gap-4">
                        <div class="pt-1"><input type="checkbox" form="weekly-report-form" name="activity_ids[]" value="{{ $activity->id }}" class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-500" aria-label="Select activity from {{ $activity->activity_date?->format('F j, Y') ?? $activity->activity_date }}"></div>
                        <div class="min-w-0 flex-1">
                            <div class="flex flex-wrap items-start justify-between gap-3"><div><p class="eyebrow">{{ $activity->activity_date?->format('l, M j, Y') ?? $activity->activity_date }}</p><h2 class="mt-1 line-clamp-2 font-bold leading-6 text-slate-950">{{ $activity->tasks_completed }}</h2></div><div class="flex gap-1"><a href="{{ route('activities.edit', $activity) }}" class="btn-ghost px-2.5">Edit</a><form method="POST" action="{{ route('activities.destroy', $activity) }}" data-confirm="Delete this activity log?">@csrf @method('DELETE')<button type="submit" class="btn-ghost px-2.5 text-rose-600">Delete</button></form></div></div>
                            <dl class="mt-4 grid gap-3 text-sm sm:grid-cols-2"><div><dt class="font-bold text-slate-700">Skills / tools</dt><dd class="mt-1 leading-6 text-slate-500">{{ $activity->skills_tools ?: 'Not specified' }}</dd></div><div><dt class="font-bold text-slate-700">Lesson learned</dt><dd class="mt-1 line-clamp-3 leading-6 text-slate-500">{{ $activity->lessons_learned ?: 'Not specified' }}</dd></div></dl>
                            @if ($activity->attachment_path)<a href="{{ route('activities.attachment', $activity) }}" class="mt-4 inline-flex text-sm font-bold text-teal-700">Download attachment</a>@endif
                        </div>
                    </article>
                @endforeach
            </section>
        @if (method_exists($activities, 'links'))<div class="mt-6">{{ $activities->withQueryString()->links() }}</div>@endif
    @endif
@endsection
