<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $user->name }} · OJT Skills Portfolio</title>
    @vite(['resources/css/app.css'])
    <style>@page { margin: 16mm; } @media print { .print-action { display: none !important; } body { background: white !important; } }</style>
</head>
<body class="bg-slate-100 text-slate-900 antialiased">
    <main class="mx-auto max-w-4xl bg-white p-6 sm:p-10 print:p-0">
        <div class="print-action mb-6 flex justify-end"><button type="button" onclick="window.print()" class="btn-primary">Print / Save PDF</button></div>
        <header class="border-b-2 border-teal-700 pb-6">
            <p class="text-xs font-bold uppercase tracking-[0.2em] text-teal-700">OJT Skills Portfolio</p>
            <h1 class="mt-2 text-3xl font-bold text-slate-950">{{ $user->name }}</h1>
            <p class="mt-1 text-sm text-slate-600">{{ $user->student_id }} · {{ $user->program }}{{ $user->section ? ' · '.$user->section : '' }}</p>
            @if ($user->studentProfile?->company_name)<p class="mt-1 text-sm text-slate-600">{{ $user->studentProfile->company_name }}{{ $user->studentProfile->department ? ' · '.$user->studentProfile->department : '' }}</p>@endif
        </header>
        <section class="mt-6 grid grid-cols-3 gap-4">
            <div><p class="text-xs font-bold uppercase text-slate-500">Activity records</p><p class="mt-1 text-2xl font-bold">{{ $activities->count() }}</p></div>
            <div><p class="text-xs font-bold uppercase text-slate-500">Skills</p><p class="mt-1 text-2xl font-bold">{{ $skills->count() }}</p></div>
            <div><p class="text-xs font-bold uppercase text-slate-500">Hours logged</p><p class="mt-1 text-2xl font-bold">{{ number_format($completedMinutes / 60, 1) }}</p></div>
        </section>
        <section class="mt-8">
            <h2 class="text-lg font-bold">Skills and tools</h2>
            <p class="mt-3 text-sm leading-7 text-slate-700">{{ $skills->pluck('name')->implode(' · ') ?: 'No skills recorded yet.' }}</p>
        </section>
        <section class="mt-8">
            <h2 class="text-lg font-bold">Experience highlights</h2>
            <div class="mt-3 divide-y divide-slate-200 border-y border-slate-200">
                @forelse ($activities as $activity)
                    <article class="break-inside-avoid py-4"><p class="text-xs font-bold uppercase text-teal-700">{{ $activity->activity_date->format('M j, Y') }}</p><h3 class="mt-1 font-bold">{{ $activity->tasks_completed }}</h3>@if ($activity->skills_tools)<p class="mt-1 text-sm"><strong>Skills:</strong> {{ $activity->skills_tools }}</p>@endif @if ($activity->lessons_learned)<p class="mt-1 text-sm"><strong>Learning:</strong> {{ $activity->lessons_learned }}</p>@endif</article>
                @empty<p class="py-5 text-sm text-slate-500">No activity evidence recorded.</p>@endforelse
            </div>
        </section>
        <footer class="mt-10 border-t border-slate-200 pt-4 text-xs text-slate-500">Generated {{ now()->format('M j, Y') }} from the student’s OJT activity records.</footer>
    </main>
</body>
</html>
