@extends('layouts.app')

@section('title', 'DTR edit history')
@section('eyebrow', 'Audit trail')
@section('page-title', 'DTR edit history')

@section('content')
    <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between"><div><p class="font-bold text-slate-950">{{ $dtr->work_date?->format('F j, Y') ?? $dtr->work_date }}</p><p class="section-copy">Official total: {{ number_format($dtr->total_minutes / 60, 2) }} hours</p></div><a href="{{ route('dtr.edit', $dtr) }}" class="btn-secondary">Edit current record</a></div>
    <section class="card card-body">
        @if ($histories->isEmpty())
            <x-empty-state title="No changes recorded" message="This DTR entry has not been edited since it was created." />
        @else
            <ol class="relative space-y-6 border-l-2 border-slate-200 pl-6">
                @foreach ($histories as $history)
                    @php $changes = is_array($history->changes) ? $history->changes : (json_decode((string) $history->changes, true) ?: []); @endphp
                    <li class="relative"><span class="absolute -left-[1.94rem] top-1.5 h-3 w-3 rounded-full bg-teal-600 ring-4 ring-white"></span><p class="text-sm font-bold text-slate-950">Changed {{ $history->created_at?->format('M j, Y \a\t g:i A') }}</p><div class="mt-3 grid gap-2">
                        @foreach ($changes as $field => $values)
                            <div class="rounded-xl bg-slate-50 px-4 py-3 text-sm"><p class="font-bold capitalize text-slate-800">{{ str_replace('_', ' ', $field) }}</p><p class="mt-1 text-slate-500"><span class="line-through">{{ is_array($values) ? ($values['old'] ?? '—') : '—' }}</span> <span aria-hidden="true">→</span> <strong class="text-slate-800">{{ is_array($values) ? ($values['new'] ?? '—') : $values }}</strong></p></div>
                        @endforeach
                    </div></li>
                @endforeach
            </ol>
        @endif
    </section>
@endsection
