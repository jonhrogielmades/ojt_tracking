@extends('layouts.app')

@section('title', 'Daily time record')
@section('eyebrow', 'Attendance')
@section('page-title', 'Daily time record')

@section('content')
    @php $formatHours = static fn (int|float|null $minutes): string => number_format(max(0, (float) $minutes) / 60, 2).' h'; @endphp
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div><p class="section-copy mt-0">One official entry per work date. Working hours are always calculated on the server.</p></div>
        <a href="{{ route('dtr.create') }}" class="btn-primary">Add DTR entry</a>
    </div>

    <section class="mb-6 grid gap-4 sm:grid-cols-2">
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">This week</p><p class="mt-3 text-3xl font-bold text-slate-950">{{ $formatHours($weeklyMinutes) }}</p></article>
        <article class="stat-card"><p class="text-sm font-semibold text-slate-500">This month</p><p class="mt-3 text-3xl font-bold text-teal-700">{{ $formatHours($monthlyMinutes) }}</p></article>
    </section>

    <section class="card overflow-hidden">
        @if ($entries->isEmpty())
            <div class="p-5 sm:p-6"><x-empty-state title="No attendance records yet" message="Create a DTR entry for each work date. You can also draft a new record while offline." :action="route('dtr.create')" action-label="Create first entry" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Work date</th><th>Time in</th><th>Break</th><th>Time out</th><th class="text-right">Official hours</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                    @foreach ($entries as $entry)
                        <tr>
                            <td><p class="font-bold text-slate-900">{{ $entry->work_date?->format('M j, Y') ?? $entry->work_date }}</p><p class="mt-0.5 max-w-xs truncate text-xs text-slate-500">{{ $entry->notes ?: 'No notes' }}</p></td>
                            <td>{{ $entry->time_in ? \Carbon\Carbon::parse($entry->time_in)->format('g:i A') : '—' }}</td>
                            <td>{{ $entry->break_start && $entry->break_end ? \Carbon\Carbon::parse($entry->break_start)->format('g:i A').'–'.\Carbon\Carbon::parse($entry->break_end)->format('g:i A') : 'None' }}</td>
                            <td>{{ $entry->time_out ? \Carbon\Carbon::parse($entry->time_out)->format('g:i A') : '—' }}</td>
                            <td class="text-right font-bold text-teal-700">{{ $formatHours($entry->total_minutes) }}</td>
                            <td>
                                <div class="flex justify-end gap-1">
                                    @if ($entry->isLocked())
                                        <span class="status-pill bg-slate-100 text-slate-600">Locked</span>
                                    @else
                                        <a href="{{ route('dtr.edit', $entry) }}" class="btn-ghost px-2.5">Edit</a>
                                        <form method="POST" action="{{ route('dtr.destroy', $entry) }}" data-confirm="Delete this DTR entry? This cannot be undone.">@csrf @method('DELETE')<button class="btn-ghost px-2.5 text-rose-600" type="submit">Delete</button></form>
                                    @endif
                                    <a href="{{ route('dtr.history', $entry) }}" class="btn-ghost px-2.5">History</a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
            @if (method_exists($entries, 'links'))<div class="border-t border-slate-200 px-5 py-4">{{ $entries->links() }}</div>@endif
        @endif
    </section>
@endsection
