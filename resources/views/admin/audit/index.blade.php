@extends('layouts.admin')

@section('title', 'Audit trail')
@section('eyebrow', 'Security & accountability')
@section('page-title', 'Audit trail')

@section('content')
    <section class="card card-body">
        <form method="GET" action="{{ route('admin.audit.index') }}" class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_15rem_11rem_11rem_auto] xl:items-end">
            <div><label for="q" class="label">Search</label><input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Event, actor, or subject"></div>
            <div><label for="event" class="label">Event</label><select id="event" name="event" class="input"><option value="">All events</option>@foreach ($events as $event)<option value="{{ $event }}" @selected(($filters['event'] ?? '') === $event)>{{ $event }}</option>@endforeach</select></div>
            <div><label for="date_from" class="label">From</label><input id="date_from" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" class="input"></div>
            <div><label for="date_to" class="label">To</label><input id="date_to" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" class="input"></div>
            <button type="submit" class="btn-primary">Apply</button>
        </form>
    </section>

    <section class="mt-6 card overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Recorded events</h2><p class="section-copy">Read-only security and administrator activity. Passwords, codes, tokens, and secrets are never recorded.</p></div>
        @if ($logs->isEmpty())
            <div class="p-6"><x-empty-state title="No audit events found" message="No events match the current filters." /></div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Time</th><th>Event</th><th>Actor</th><th>Subject</th><th>Network</th><th>Context</th></tr></thead>
                    <tbody>
                        @foreach ($logs as $log)
                            <tr>
                                <td class="whitespace-nowrap"><time datetime="{{ $log->created_at?->toIso8601String() }}">{{ $log->created_at?->format('M j, Y g:i A') }}</time></td>
                                <td><span class="font-mono text-xs font-bold text-teal-700">{{ $log->event }}</span></td>
                                <td><p class="font-semibold text-slate-900">{{ $log->actor?->name ?? 'System / unknown' }}</p>@if ($log->actor)<p class="mt-0.5 text-xs text-slate-500">{{ $log->actor->email }}</p>@endif</td>
                                <td><p class="max-w-48 truncate text-xs text-slate-600" title="{{ $log->subject_type }}">{{ class_basename((string) $log->subject_type) ?: '—' }}</p><p class="font-mono text-xs text-slate-500">{{ $log->subject_id ?? '—' }}</p></td>
                                <td class="font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                                <td><details class="max-w-xs"><summary class="cursor-pointer text-xs font-bold text-slate-600">View safe context</summary><pre class="mt-2 max-h-40 overflow-auto whitespace-pre-wrap rounded-lg bg-slate-950 p-3 text-[0.7rem] text-slate-200">{{ json_encode(['before' => $log->old_values, 'after' => $log->new_values, 'metadata' => $log->metadata], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</pre></details></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $logs->links() }}</div>
        @endif
    </section>
@endsection
