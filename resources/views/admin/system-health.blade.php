@extends('layouts.admin')

@section('title', 'System health')
@section('eyebrow', 'Operational visibility')
@section('page-title', 'System health')

@section('content')
    @php
        $storageLabel = $freeStorageBytes === null ? 'Unavailable' : number_format($freeStorageBytes / 1_073_741_824, 1).' GB free';
    @endphp
    <section aria-label="System checks" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Database</p><p @class(['mt-3 text-2xl font-bold', 'text-emerald-700' => $databaseHealthy, 'text-rose-700' => ! $databaseHealthy])>{{ $databaseHealthy ? 'Connected' : 'Unavailable' }}</p><p class="mt-2 text-xs uppercase text-slate-500">{{ $databaseDriver }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Private storage</p><p @class(['mt-3 text-2xl font-bold', 'text-emerald-700' => $storageWritable, 'text-rose-700' => ! $storageWritable])>{{ $storageWritable ? 'Writable' : 'Needs attention' }}</p><p class="mt-2 text-xs text-slate-500">{{ $storageLabel }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Failed queue jobs</p><p @class(['mt-3 text-3xl font-bold', 'text-slate-950' => ($failedJobs ?? 0) === 0, 'text-rose-700' => ($failedJobs ?? 0) > 0])>{{ $failedJobs === null ? 'N/A' : number_format($failedJobs) }}</p><p class="mt-2 text-xs text-slate-500">Connection: {{ $queueConnection }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">Latest backup</p><p class="mt-3 text-xl font-bold text-slate-950">{{ $latestBackup?->created_at?->diffForHumans() ?? 'None recorded' }}</p><p class="mt-2 text-xs text-slate-500">{{ $latestBackup ? ucfirst($latestBackup->status) : 'Create a recovery point' }}</p></article>
    </section>

    <section class="mt-6 card card-body">
        <h2 class="section-title">Runtime information</h2>
        <p class="section-copy">Non-sensitive version and service information for troubleshooting.</p>
        <dl class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Environment</dt><dd class="mt-1 font-semibold text-slate-900">{{ $appEnvironment }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Laravel</dt><dd class="mt-1 font-semibold text-slate-900">{{ $laravelVersion }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">PHP</dt><dd class="mt-1 font-semibold text-slate-900">{{ $phpVersion }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Cache store</dt><dd class="mt-1 font-semibold text-slate-900">{{ $cacheStore }}</dd></div>
            <div class="rounded-xl bg-slate-50 p-4"><dt class="text-xs font-bold uppercase tracking-wider text-slate-500">Queue connection</dt><dd class="mt-1 font-semibold text-slate-900">{{ $queueConnection }}</dd></div>
        </dl>
    </section>
@endsection
