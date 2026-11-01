@extends('layouts.app')

@section('title', 'OJT calendar')
@section('eyebrow', 'Schedule')
@section('page-title', 'OJT calendar')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-3">
        <a href="{{ route('student.calendar', ['month' => $previousMonth]) }}" class="btn-secondary" aria-label="Previous month">← Previous</a>
        <h2 class="text-lg font-bold text-slate-950 sm:text-xl">{{ $month->format('F Y') }}</h2>
        <a href="{{ route('student.calendar', ['month' => $nextMonth]) }}" class="btn-secondary" aria-label="Next month">Next →</a>
    </div>

    <section class="card overflow-x-auto">
        <div class="min-w-[56rem]">
            <div class="grid grid-cols-7 border-b border-slate-200 bg-slate-50">@foreach (['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'] as $day)<div class="px-3 py-3 text-center text-xs font-bold uppercase tracking-wide text-slate-500">{{ $day }}</div>@endforeach</div>
            <div class="grid grid-cols-7">
                @foreach ($days as $day)
                    <article class="min-h-36 border-b border-r border-slate-200 p-3 {{ $day['in_month'] ? 'bg-white' : 'bg-slate-50/70 text-slate-400' }}">
                        <div class="flex items-start justify-between gap-2"><span class="text-sm font-bold">{{ $day['date']->day }}</span>@if ($day['date']->isToday())<span class="status-pill bg-teal-100 text-teal-800">Today</span>@endif</div>
                        @if ($day['holiday'])<p class="mt-2 rounded-lg bg-rose-50 px-2 py-1 text-xs font-semibold text-rose-700">{{ $day['holiday']['name'] }}</p>@endif
                        @foreach ($day['dtr'] as $entry)<a href="{{ route('dtr.history', $entry) }}" class="mt-2 block rounded-lg bg-teal-50 px-2 py-1.5 text-xs font-semibold text-teal-800">DTR · {{ number_format($entry->total_minutes / 60, 1) }}h</a>@endforeach
                        @foreach ($day['activities'] as $activity)<a href="{{ route('activities.edit', $activity) }}" class="mt-2 block truncate rounded-lg bg-blue-50 px-2 py-1.5 text-xs font-semibold text-blue-800" title="{{ $activity->tasks_completed }}">Activity · {{ $activity->tasks_completed }}</a>@endforeach
                    </article>
                @endforeach
            </div>
        </div>
    </section>
    <p class="mt-3 text-xs text-slate-500">Swipe horizontally on smaller screens. Holidays come from the active academic term when one is configured.</p>
@endsection
