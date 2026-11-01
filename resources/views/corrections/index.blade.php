@extends('layouts.app')

@section('title', 'Correction requests')
@section('eyebrow', 'Attendance review')
@section('page-title', 'Correction requests')

@section('content')
    <p class="section-copy mt-0 mb-6 max-w-3xl">Approved DTR entries are protected. Explain the correction you need; if approved, the week unlocks so you can update and resubmit it.</p>

    <section class="card card-body mb-6">
        <h2 class="section-title">Request a correction</h2>
        <div class="mt-5 space-y-4">
            @forelse ($eligibleDtrEntries as $entry)
                <form method="POST" action="{{ route('corrections.store', $entry) }}" class="rounded-2xl border border-slate-200 p-4">
                    @csrf
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                        <div><p class="font-bold text-slate-950">{{ $entry->work_date->format('M j, Y') }}</p><p class="text-sm text-slate-500">{{ number_format($entry->total_minutes / 60, 2) }} official hours</p></div>
                    </div>
                    <div class="mt-4"><label for="reason-{{ $entry->id }}" class="label">What needs to change?</label><textarea id="reason-{{ $entry->id }}" name="reason" rows="3" required minlength="20" class="input" placeholder="Describe the incorrect time, date, or note and the correct value.">{{ old('reason') }}</textarea></div>
                    <div class="mt-3 flex justify-end"><button type="submit" class="btn-primary">Send request</button></div>
                </form>
            @empty
                <p class="rounded-2xl bg-slate-50 p-4 text-sm text-slate-600">No approved DTR entries are currently eligible for a new correction request.</p>
            @endforelse
        </div>
    </section>

    <section class="card overflow-hidden">
        <div class="card-body pb-3"><h2 class="section-title">Request history</h2></div>
        @if ($correctionRequests->isEmpty())
            <div class="px-5 pb-6 text-sm text-slate-500">You have not submitted a correction request.</div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>DTR date</th><th>Reason</th><th>Status</th><th>Response</th></tr></thead>
                    <tbody>
                        @foreach ($correctionRequests as $correction)
                            <tr>
                                <td class="whitespace-nowrap font-bold text-slate-900">{{ $correction->dtrEntry?->work_date?->format('M j, Y') ?? 'Deleted entry' }}</td>
                                <td class="min-w-64 text-sm">{{ $correction->reason }}</td>
                                <td><span class="status-pill {{ $correction->status === \App\Enums\CorrectionRequestStatus::Approved ? 'bg-emerald-100 text-emerald-800' : ($correction->status === \App\Enums\CorrectionRequestStatus::Rejected ? 'bg-rose-100 text-rose-800' : 'bg-amber-100 text-amber-800') }}">{{ $correction->status->label() }}</span></td>
                                <td class="min-w-56 text-sm text-slate-600">{{ $correction->reviewer_response ?: 'Awaiting review' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @if ($correctionRequests->hasPages())<div class="border-t border-slate-200 p-4">{{ $correctionRequests->links() }}</div>@endif
        @endif
    </section>
@endsection
