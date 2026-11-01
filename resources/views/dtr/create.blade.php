@extends('layouts.app')

@section('title', 'Add DTR entry')
@section('eyebrow', 'Attendance')
@section('page-title', 'Add DTR entry')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex items-center justify-between gap-4"><p class="section-copy mt-0">If you lose your connection, this form can save an unsynced draft on this device.</p><a href="{{ route('dtr.index') }}" class="btn-ghost">Back</a></div>
        <form method="POST" action="{{ route('dtr.store') }}" data-offline-form data-kind="dtr" data-sync-url="{{ route('sync.dtr') }}" data-success-url="{{ route('dtr.index') }}" class="card card-body space-y-6">
            @csrf
            <input type="hidden" name="idempotency_key" value="{{ old('idempotency_key', (string) Illuminate\Support\Str::uuid()) }}">
            <div data-form-status hidden class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm leading-6 text-rose-800" role="alert" tabindex="-1"></div>
            <div><label for="work_date" class="label">Work date</label><input id="work_date" name="work_date" type="date" value="{{ old('work_date', request('date', now()->format('Y-m-d'))) }}" required @class(['input', 'input-error' => $errors->has('work_date')])><x-field-error name="work_date" /></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="time_in" class="label">Time in</label><input id="time_in" name="time_in" type="time" value="{{ old('time_in') }}" required @class(['input', 'input-error' => $errors->has('time_in')])><x-field-error name="time_in" /></div>
                <div><label for="time_out" class="label">Time out</label><input id="time_out" name="time_out" type="time" value="{{ old('time_out') }}" required @class(['input', 'input-error' => $errors->has('time_out')])><x-field-error name="time_out" /></div>
                <div><label for="break_start" class="label">Break start <span class="font-normal text-slate-400">(optional)</span></label><input id="break_start" name="break_start" type="time" value="{{ old('break_start') }}" @class(['input', 'input-error' => $errors->has('break_start')])><x-field-error name="break_start" /></div>
                <div><label for="break_end" class="label">Break end <span class="font-normal text-slate-400">(optional)</span></label><input id="break_end" name="break_end" type="time" value="{{ old('break_end') }}" @class(['input', 'input-error' => $errors->has('break_end')])><x-field-error name="break_end" /></div>
            </div>
            <p class="-mt-2 rounded-xl bg-slate-50 px-4 py-3 text-xs leading-5 text-slate-600">Enter both break times or leave both blank. Breaks must fall between time in and time out. Your official total is calculated after the server validates the entry.</p>
            <div><label for="notes" class="label">Notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="notes" name="notes" rows="4" @class(['input', 'input-error' => $errors->has('notes')]) placeholder="Anything relevant about this workday">{{ old('notes') }}</textarea><x-field-error name="notes" /></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('dtr.index') }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Save DTR entry</button></div>
        </form>
    </div>
@endsection
