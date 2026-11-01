@extends('layouts.app')

@section('title', 'Edit DTR entry')
@section('eyebrow', 'Attendance')
@section('page-title', 'Edit DTR entry')

@section('content')
    <div class="mx-auto max-w-3xl">
        <div class="mb-5 flex items-center justify-between gap-4"><p class="section-copy mt-0">Changes to a saved record are captured in its edit history.</p><a href="{{ route('dtr.history', $dtr) }}" class="btn-secondary">View history</a></div>
        <form method="POST" action="{{ route('dtr.update', $dtr) }}" class="card card-body space-y-6">
            @csrf @method('PUT')
            <div><label for="work_date" class="label">Work date</label><input id="work_date" name="work_date" type="date" value="{{ old('work_date', $dtr->work_date?->format('Y-m-d')) }}" required @class(['input', 'input-error' => $errors->has('work_date')])><x-field-error name="work_date" /></div>
            <div class="grid gap-5 sm:grid-cols-2">
                <div><label for="time_in" class="label">Time in</label><input id="time_in" name="time_in" type="time" value="{{ old('time_in', substr((string) $dtr->time_in, 0, 5)) }}" required @class(['input', 'input-error' => $errors->has('time_in')])><x-field-error name="time_in" /></div>
                <div><label for="time_out" class="label">Time out</label><input id="time_out" name="time_out" type="time" value="{{ old('time_out', substr((string) $dtr->time_out, 0, 5)) }}" required @class(['input', 'input-error' => $errors->has('time_out')])><x-field-error name="time_out" /></div>
                <div><label for="break_start" class="label">Break start <span class="font-normal text-slate-400">(optional)</span></label><input id="break_start" name="break_start" type="time" value="{{ old('break_start', $dtr->break_start ? substr((string) $dtr->break_start, 0, 5) : '') }}" @class(['input', 'input-error' => $errors->has('break_start')])><x-field-error name="break_start" /></div>
                <div><label for="break_end" class="label">Break end <span class="font-normal text-slate-400">(optional)</span></label><input id="break_end" name="break_end" type="time" value="{{ old('break_end', $dtr->break_end ? substr((string) $dtr->break_end, 0, 5) : '') }}" @class(['input', 'input-error' => $errors->has('break_end')])><x-field-error name="break_end" /></div>
            </div>
            <div><label for="notes" class="label">Notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="notes" name="notes" rows="4" @class(['input', 'input-error' => $errors->has('notes')])>{{ old('notes', $dtr->notes) }}</textarea><x-field-error name="notes" /></div>
            <div class="flex flex-col-reverse gap-3 border-t border-slate-200 pt-5 sm:flex-row sm:justify-end"><a href="{{ route('dtr.index') }}" class="btn-secondary">Cancel</a><button type="submit" class="btn-primary">Update DTR entry</button></div>
        </form>
    </div>
@endsection
