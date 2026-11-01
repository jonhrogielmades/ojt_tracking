@extends('layouts.app')

@section('title', 'Monthly DTR report')
@section('eyebrow', 'Reports')
@section('page-title', 'Monthly DTR report')

@section('content')
    @php $formatHours = static fn (int|float|null $minutes): string => number_format(max(0, (float) $minutes) / 60, 2).' h'; @endphp
    <div class="mb-6 flex flex-col gap-4 print:hidden sm:flex-row sm:items-end sm:justify-between">
        <form method="GET" action="{{ route('reports.monthly') }}" class="flex flex-col gap-2 sm:flex-row sm:items-end"><div><label for="month" class="label">Report month</label><input id="month" name="month" type="month" value="{{ $month->format('Y-m') }}" class="input"></div><button type="submit" class="btn-secondary">View month</button></form>
        <div class="flex flex-wrap gap-2"><a href="{{ route('reports.weekly') }}" class="btn-secondary">Weekly report</a><a href="{{ route('reports.dtr_csv', ['month' => $month->format('Y-m')]) }}" class="btn-secondary">Export CSV</a><button type="button" onclick="window.print()" class="btn-primary">Print / Save as PDF</button></div>
    </div>

    <section class="card overflow-hidden print:border-0">
        <header class="border-b border-slate-200 bg-slate-50 px-5 py-6 text-center sm:px-8 print:bg-white">
            <p class="text-xs font-bold uppercase tracking-[0.25em] text-slate-500">{{ $reportTemplate?->institution_name ?? config('app.name') }}</p>
            @if ($reportTemplate?->office_name)<p class="mt-1 text-xs font-semibold text-slate-500">{{ $reportTemplate->office_name }}</p>@endif
            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-950">{{ $reportTemplate?->monthly_title ?? 'Daily Time Record' }}</h2><p class="mt-1 font-semibold text-teal-700">{{ $month->format('F Y') }}</p>
        </header>
        <div class="grid gap-x-8 gap-y-3 border-b border-slate-200 px-5 py-5 text-sm sm:grid-cols-2 sm:px-8">
            <p><span class="font-bold text-slate-600">Student:</span> {{ auth()->user()->name }}</p><p><span class="font-bold text-slate-600">Student ID:</span> {{ auth()->user()->student_id }}</p><p><span class="font-bold text-slate-600">Program / section:</span> {{ auth()->user()->program }} · {{ auth()->user()->section }}</p><p><span class="font-bold text-slate-600">Company:</span> {{ $profile?->company_name ?: 'Not provided' }}</p>
        </div>
        @if ($dtrEntries->isEmpty())
            <div class="p-6"><x-empty-state title="No DTR entries for this month" message="Choose another month or add attendance records before printing." /></div>
        @else
            <div class="overflow-x-auto"><table class="data-table"><thead><tr><th>Date</th><th>Time in</th><th>Break start</th><th>Break end</th><th>Time out</th><th class="text-right">Hours</th></tr></thead><tbody>
                @foreach ($dtrEntries as $entry)
                    <tr><td class="font-semibold text-slate-900">{{ $entry->work_date?->format('D, M j') ?? $entry->work_date }}</td><td>{{ $entry->time_in ? \Carbon\Carbon::parse($entry->time_in)->format('g:i A') : '—' }}</td><td>{{ $entry->break_start ? \Carbon\Carbon::parse($entry->break_start)->format('g:i A') : '—' }}</td><td>{{ $entry->break_end ? \Carbon\Carbon::parse($entry->break_end)->format('g:i A') : '—' }}</td><td>{{ $entry->time_out ? \Carbon\Carbon::parse($entry->time_out)->format('g:i A') : '—' }}</td><td class="text-right font-bold">{{ $formatHours($entry->total_minutes) }}</td></tr>
                @endforeach
            </tbody><tfoot class="border-t-2 border-slate-300 bg-slate-50"><tr><td colspan="5" class="px-4 py-3 text-right font-bold text-slate-700">Monthly total</td><td class="px-4 py-3 text-right font-bold text-teal-700">{{ $formatHours($monthlyMinutes) }}</td></tr></tfoot></table></div>
        @endif
        <div class="grid gap-4 border-t border-slate-200 p-5 sm:grid-cols-3 sm:p-8"><div class="rounded-xl bg-slate-50 p-4 text-center"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Completed overall</p><p class="mt-2 text-xl font-bold">{{ $formatHours($completedMinutes) }}</p></div><div class="rounded-xl bg-slate-50 p-4 text-center"><p class="text-xs font-bold uppercase tracking-wider text-slate-500">Required</p><p class="mt-2 text-xl font-bold">{{ $formatHours($requiredMinutes) }}</p></div><div class="rounded-xl bg-teal-50 p-4 text-center"><p class="text-xs font-bold uppercase tracking-wider text-teal-700">Remaining</p><p class="mt-2 text-xl font-bold text-teal-800">{{ $formatHours($remainingMinutes) }}</p></div></div>
        <div class="grid gap-12 px-8 pb-8 pt-16 sm:grid-cols-3"><div class="signature-line">{{ $reportTemplate?->prepared_by_label ?? 'Student' }}</div><div class="signature-line">Company supervisor</div><div class="signature-line">{{ $reportTemplate?->signatory_name ?: ($reportTemplate?->approved_by_label ?? 'School coordinator') }}@if ($reportTemplate?->signatory_title)<span class="mt-1 block text-xs font-normal text-slate-500">{{ $reportTemplate->signatory_title }}</span>@endif</div></div>
        <p class="px-8 pb-6 text-center text-xs text-slate-400">Signature lines are optional. This system does not claim approval or verification by a supervisor.</p>
    </section>
@endsection
