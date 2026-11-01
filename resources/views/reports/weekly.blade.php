@extends('layouts.app')

@section('title', 'Weekly accomplishment report')
@section('eyebrow', 'Reports')
@section('page-title', 'Weekly accomplishment report')

@section('content')
    <div class="mb-6 flex flex-col gap-4 print:hidden sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" action="{{ route('reports.weekly') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end"><div><label for="week" class="label">Week containing</label><input id="week" name="week" type="date" value="{{ $weekStart->format('Y-m-d') }}" class="input"></div><button type="submit" class="btn-secondary">View week</button></form>
        <div class="flex flex-wrap gap-2"><a href="{{ route('activities.index', ['from' => $weekStart->format('Y-m-d'), 'to' => $weekEnd->format('Y-m-d')]) }}" class="btn-secondary">Select activities</a><a href="{{ route('reports.monthly') }}" class="btn-secondary">Monthly DTR</a><button type="button" onclick="window.print()" class="btn-primary">Print / Save as PDF</button></div>
    </div>

    <section class="card overflow-hidden print:border-0">
        <header class="border-b border-slate-200 bg-slate-50 px-5 py-6 text-center sm:px-8 print:bg-white"><p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">{{ $reportTemplate?->institution_name ?? config('app.name') }}</p>@if ($reportTemplate?->office_name)<p class="mt-1 text-xs font-semibold text-slate-500">{{ $reportTemplate->office_name }}</p>@endif<h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $reportTemplate?->weekly_title ?? 'Weekly Accomplishment Report' }}</h2><p class="mt-1 font-semibold text-teal-700">{{ $weekStart->format('F j') }} – {{ $weekEnd->format('F j, Y') }}</p></header>
        <div class="grid gap-x-8 gap-y-3 border-b border-slate-200 px-5 py-5 text-sm sm:grid-cols-2 sm:px-8"><p><span class="font-bold text-slate-600">Student:</span> {{ auth()->user()->name }}</p><p><span class="font-bold text-slate-600">Program / section:</span> {{ auth()->user()->program }} · {{ auth()->user()->section }}</p><p><span class="font-bold text-slate-600">Company:</span> {{ $profile?->company_name ?: 'Not provided' }}</p><p><span class="font-bold text-slate-600">DTR hours this week:</span> {{ number_format($weeklyDtrMinutes / 60, 2) }}</p></div>

        @php $reportActivities = $selectedActivityLogs->isNotEmpty() ? $selectedActivityLogs : $activityLogs; @endphp
        @if ($reportActivities->isEmpty())
            <div class="p-6"><x-empty-state title="No activity logs for this week" message="Add an activity or choose a different week before printing this report." :action="route('activities.create')" action-label="Add activity" /></div>
        @else
            <div class="divide-y divide-slate-200">
                @foreach ($reportActivities as $activity)
                    <article class="grid gap-4 px-5 py-6 sm:grid-cols-[10rem_1fr] sm:px-8"><div><p class="eyebrow">{{ $activity->activity_date?->format('l') }}</p><p class="mt-1 text-sm font-bold text-slate-900">{{ $activity->activity_date?->format('M j, Y') }}</p></div><div class="space-y-4"><div><h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Tasks completed</h3><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-800">{{ $activity->tasks_completed }}</p></div>@if ($activity->skills_tools)<div><h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Skills / tools used</h3><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $activity->skills_tools }}</p></div>@endif @if ($activity->lessons_learned)<div><h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Lessons learned</h3><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $activity->lessons_learned }}</p></div>@endif @if ($activity->challenges_notes)<div><h3 class="text-xs font-bold uppercase tracking-wider text-slate-500">Challenges / notes</h3><p class="mt-1 whitespace-pre-line text-sm leading-6 text-slate-700">{{ $activity->challenges_notes }}</p></div>@endif</div></article>
                @endforeach
            </div>
        @endif

        <div class="grid gap-12 border-t border-slate-200 px-8 pb-8 pt-16 sm:grid-cols-3"><div class="signature-line">{{ $reportTemplate?->prepared_by_label ?? 'Student' }}</div><div class="signature-line">Company supervisor</div><div class="signature-line">{{ $reportTemplate?->signatory_name ?: ($reportTemplate?->approved_by_label ?? 'School coordinator') }}@if ($reportTemplate?->signatory_title)<span class="mt-1 block text-xs font-normal text-slate-500">{{ $reportTemplate->signatory_title }}</span>@endif</div></div><p class="px-8 pb-6 text-center text-xs text-slate-400">Signature lines are optional. This report is not marked approved or signed by the application.</p>
    </section>
@endsection
