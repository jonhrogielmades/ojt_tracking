@extends('layouts.app')

@section('title', 'Skills portfolio')
@section('eyebrow', 'Professional growth')
@section('page-title', 'Skills portfolio')

@section('content')
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <p class="section-copy mt-0 max-w-2xl">A personal evidence summary built from your activity logs. Keep skills and lessons specific so this stays useful for interviews and evaluation.</p>
        <a href="{{ route('student.portfolio.print') }}" target="_blank" class="btn-primary">Printable portfolio</a>
    </div>

    <section class="grid gap-4 sm:grid-cols-3">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Activity records</p><p class="mt-3 text-3xl font-bold text-slate-950">{{ $activities->count() }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Skills identified</p><p class="mt-3 text-3xl font-bold text-blue-700">{{ $skills->count() }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">OJT hours logged</p><p class="mt-3 text-3xl font-bold text-teal-700">{{ number_format($completedMinutes / 60, 1) }}</p></article>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[minmax(18rem,.8fr)_minmax(0,1.5fr)]">
        <section class="card card-body">
            <h2 class="section-title">Skills and tools</h2>
            <div class="mt-5 flex flex-wrap gap-2">
                @forelse ($skills as $skill)<span class="rounded-full bg-teal-50 px-3 py-1.5 text-sm font-semibold text-teal-800">{{ $skill['name'] }} <span class="text-teal-500">×{{ $skill['count'] }}</span></span>@empty<p class="text-sm text-slate-500">Add skills and tools to your activity logs to build this list.</p>@endforelse
            </div>
        </section>
        <section class="card overflow-hidden">
            <div class="card-body pb-3"><h2 class="section-title">Experience highlights</h2></div>
            <div class="divide-y divide-slate-200">
                @forelse ($activities as $activity)
                    <article class="p-5 sm:p-6">
                        <p class="text-xs font-bold uppercase tracking-wide text-teal-700">{{ $activity->activity_date->format('M j, Y') }}</p>
                        <h3 class="mt-2 font-bold text-slate-950">{{ $activity->tasks_completed }}</h3>
                        @if ($activity->skills_tools)<p class="mt-2 text-sm text-slate-600"><span class="font-semibold">Skills:</span> {{ $activity->skills_tools }}</p>@endif
                        @if ($activity->lessons_learned)<p class="mt-2 text-sm leading-6 text-slate-600"><span class="font-semibold">Learning:</span> {{ $activity->lessons_learned }}</p>@endif
                    </article>
                @empty
                    <div class="p-6"><x-empty-state title="No portfolio evidence yet" message="Your activity logs become portfolio highlights automatically." :action="route('activities.create')" action-label="Add an activity" /></div>
                @endforelse
            </div>
        </section>
    </div>
@endsection
