@extends('layouts.app')

@section('title', 'Dashboard')
@section('eyebrow', now()->format('l, F j'))
@section('page-title', 'Your OJT progress')

@section('content')
    @php
        $formatHours = static fn (int|float|null $minutes): string => number_format(max(0, (float) $minutes) / 60, 1).' h';
        $profileReady = $profile && $profile->company_name && $requiredMinutes > 0;
    @endphp

    @unless ($profileReady)
        <div class="mb-6 flex flex-col gap-4 rounded-2xl border border-amber-200 bg-amber-50 p-5 sm:flex-row sm:items-center sm:justify-between">
            <div><p class="font-bold text-amber-950">Finish your placement profile</p><p class="mt-1 text-sm leading-6 text-amber-800">Add your company, schedule, OJT dates, and required hours so the dashboard can calculate accurate progress.</p></div>
            <a href="{{ route('profile.edit') }}" class="btn-secondary shrink-0 border-amber-300 bg-amber-50 text-amber-900">Complete profile</a>
        </div>
    @endunless

    <section aria-label="Hour progress" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Required hours</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $formatHours($requiredMinutes) }}</p><p class="mt-2 text-xs text-slate-500">Your program target</p></article>
        <article class="stat-card"><div class="absolute -right-7 -top-7 h-24 w-24 rounded-full bg-teal-100"></div><p class="relative text-sm font-semibold text-slate-500">Completed</p><p class="relative mt-3 text-3xl font-bold tracking-tight text-teal-700">{{ $formatHours($completedMinutes) }}</p><p class="relative mt-2 text-xs text-slate-500">Official server total</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Remaining</p><p class="mt-3 text-3xl font-bold tracking-tight text-slate-950">{{ $formatHours($remainingMinutes) }}</p><p class="mt-2 text-xs text-slate-500">Until your requirement</p></article>
        <article class="stat-card bg-slate-950 text-white"><p class="text-sm font-semibold text-slate-400">Overall progress</p><p class="mt-3 text-3xl font-bold tracking-tight">{{ number_format((float) $completionPercent, 0) }}%</p><div class="mt-4 h-2 overflow-hidden rounded-full bg-white/10"><div class="h-full rounded-full bg-teal-400" style="width: {{ min(100, max(0, (float) $completionPercent)) }}%"></div></div></article>
    </section>

    <section class="mt-6 grid gap-4 sm:grid-cols-3" aria-label="Quick actions">
        <a href="{{ route('dtr.create') }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:border-teal-300"><div><p class="eyebrow">Attendance</p><p class="mt-1 font-bold text-slate-950">Add DTR entry</p></div><span class="text-2xl text-teal-600 transition group-hover:translate-x-1">→</span></a>
        <a href="{{ route('activities.create') }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:border-teal-300"><div><p class="eyebrow">Accomplishment</p><p class="mt-1 font-bold text-slate-950">Log an activity</p></div><span class="text-2xl text-teal-600 transition group-hover:translate-x-1">→</span></a>
        <a href="{{ route('reports.monthly') }}" class="group card flex items-center justify-between gap-4 p-5 transition hover:-translate-y-0.5 hover:border-teal-300"><div><p class="eyebrow">Summary</p><p class="mt-1 font-bold text-slate-950">Open reports</p></div><span class="text-2xl text-teal-600 transition group-hover:translate-x-1">→</span></a>
    </section>

    <div class="mt-6 grid gap-6 xl:grid-cols-[1.35fr_0.65fr]">
        <section class="card overflow-hidden">
            <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div><h2 class="section-title">Recent attendance</h2><p class="section-copy">Your latest official DTR records</p></div><a href="{{ route('dtr.index') }}" class="text-sm font-bold text-teal-700">View all</a></div>
            @if ($recentDtrEntries->isEmpty())
                <div class="p-5 sm:p-6"><x-empty-state title="No DTR entries yet" message="Record your first workday. Official hours are calculated after syncing." :action="route('dtr.create')" action-label="Add DTR entry" /></div>
            @else
                <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Date</th><th>Time in</th><th>Time out</th><th class="text-right">Hours</th></tr></thead><tbody>
                    @foreach ($recentDtrEntries as $entry)
                        <tr><td class="font-semibold text-slate-900">{{ $entry->work_date?->format('M j, Y') ?? $entry->work_date }}</td><td>{{ $entry->time_in ? \Carbon\Carbon::parse($entry->time_in)->format('g:i A') : '—' }}</td><td>{{ $entry->time_out ? \Carbon\Carbon::parse($entry->time_out)->format('g:i A') : '—' }}</td><td class="text-right font-bold text-teal-700">{{ $formatHours($entry->total_minutes) }}</td></tr>
                    @endforeach
                </tbody></table></div>
            @endif
        </section>

        <section class="card card-body">
            <div class="flex items-start justify-between gap-4"><div><h2 class="section-title">Needs attention</h2><p class="section-copy">Recent expected workdays without complete records</p></div><span class="rounded-full bg-amber-100 px-2.5 py-1 text-xs font-bold text-amber-800">{{ collect($missingDates)->count() + collect($incompleteDates)->count() }}</span></div>
            <div class="mt-5 space-y-3">
                @forelse (collect($missingDates)->take(5) as $date)
                    <div class="flex items-center justify-between gap-3 rounded-xl bg-amber-50 px-3 py-3"><div><p class="text-sm font-bold text-amber-950">Missing DTR</p><p class="text-xs text-amber-700">{{ $date instanceof \Carbon\CarbonInterface ? $date->format('D, M j') : $date }}</p></div><a href="{{ route('dtr.create', ['date' => $date instanceof \Carbon\CarbonInterface ? $date->format('Y-m-d') : $date]) }}" class="text-xs font-bold text-amber-900">Add</a></div>
                @empty
                    @if (collect($incompleteDates)->isEmpty())<p class="rounded-xl bg-emerald-50 px-4 py-4 text-sm font-semibold text-emerald-800">You’re caught up. No recent gaps found.</p>@endif
                @endforelse
                @foreach (collect($incompleteDates)->take(5) as $date)
                    <div class="rounded-xl bg-sky-50 px-3 py-3"><p class="text-sm font-bold text-sky-950">Activity log missing</p><p class="text-xs text-sky-700">{{ $date instanceof \Carbon\CarbonInterface ? $date->format('D, M j') : $date }}</p></div>
                @endforeach
            </div>
        </section>
    </div>

    <section class="mt-6 card overflow-hidden">
        <div class="flex items-center justify-between gap-4 border-b border-slate-200 px-5 py-4 sm:px-6"><div><h2 class="section-title">Recent activities</h2><p class="section-copy">A quick view of your latest accomplishments</p></div><a href="{{ route('activities.index') }}" class="text-sm font-bold text-teal-700">View all</a></div>
        @if ($recentActivityLogs->isEmpty())
            <div class="p-5 sm:p-6"><x-empty-state title="No activities yet" message="Capture completed tasks, tools used, lessons learned, and challenges." :action="route('activities.create')" action-label="Log an activity" /></div>
        @else
            <div class="grid divide-y divide-slate-100 md:grid-cols-2 md:divide-x md:divide-y-0">
                @foreach ($recentActivityLogs as $activity)
                    <article class="p-5 sm:p-6"><p class="text-xs font-bold uppercase tracking-wider text-teal-700">{{ $activity->activity_date?->format('M j, Y') ?? $activity->activity_date }}</p><p class="mt-2 line-clamp-2 text-sm font-semibold leading-6 text-slate-900">{{ $activity->tasks_completed }}</p>@if ($activity->skills_tools)<p class="mt-2 text-xs text-slate-500">Tools: {{ $activity->skills_tools }}</p>@endif</article>
                @endforeach
            </div>
        @endif
    </section>
@endsection
