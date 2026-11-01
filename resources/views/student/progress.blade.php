@extends('layouts.app')

@section('title', 'Completion forecast')
@section('eyebrow', 'Progress planning')
@section('page-title', 'Completion forecast')

@section('content')
    @php
        $progressPercent = $requiredMinutes > 0 ? min(100, round($completedMinutes / $requiredMinutes * 100, 1)) : 0;
        $weeklyPercent = min(100, round($currentWeekMinutes / $weeklyTargetMinutes * 100, 1));
    @endphp
    <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Completed</p><p class="mt-3 text-3xl font-bold text-teal-700">{{ number_format($completedMinutes / 60, 1) }}h</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Required</p><p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($requiredMinutes / 60, 1) }}h</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Remaining</p><p class="mt-3 text-3xl font-bold text-amber-700">{{ number_format($remainingMinutes / 60, 1) }}h</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Forecast</p><p class="mt-3 text-xl font-bold text-slate-950">{{ $remainingMinutes > 0 ? $forecastDate->format('M j, Y') : 'Complete' }}</p><p class="mt-1 text-xs text-slate-500">{{ $weeksRemaining }} {{ Str::plural('week', $weeksRemaining) }} at your target</p></article>
    </section>

    <div class="mt-6 grid gap-6 lg:grid-cols-2">
        <section class="card card-body">
            <h2 class="section-title">Overall completion</h2>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-teal-600" style="width: {{ $progressPercent }}%"></div></div>
            <div class="mt-2 flex justify-between text-sm"><span class="text-slate-500">Verified DTR hours</span><span class="font-bold text-slate-900">{{ $progressPercent }}%</span></div>
            @if ($requiredMinutes === 0)<p class="mt-4 rounded-xl bg-amber-50 p-3 text-sm text-amber-800">Set your required OJT hours in Profile & placement to activate an accurate completion forecast.</p>@endif
        </section>
        <section class="card card-body">
            <h2 class="section-title">Weekly target</h2>
            <div class="mt-5 h-3 overflow-hidden rounded-full bg-slate-100"><div class="h-full rounded-full bg-blue-600" style="width: {{ $weeklyPercent }}%"></div></div>
            <p class="mt-2 text-sm text-slate-600">{{ number_format($currentWeekMinutes / 60, 1) }} of {{ number_format($weeklyTargetMinutes / 60, 1) }} hours logged this week.</p>
            <form method="POST" action="{{ route('student.progress.update') }}" class="mt-5 flex flex-col gap-3 sm:flex-row sm:items-end">
                @csrf @method('PATCH')
                <div class="flex-1"><label for="weekly_target_hours" class="label">Target hours per week</label><input id="weekly_target_hours" name="weekly_target_hours" type="number" min="1" max="168" step="0.25" value="{{ old('weekly_target_hours', $weeklyTargetMinutes / 60) }}" required class="input"><x-field-error name="weekly_target_hours" /></div>
                <button type="submit" class="btn-primary">Update target</button>
            </form>
        </section>
    </div>
@endsection
