@extends('layouts.app')

@section('title', 'Profile & placement')
@section('eyebrow', 'Account settings')
@section('page-title', 'Profile & OJT placement')

@section('content')
    @php
        $selectedWorkDays = array_map('intval', old('work_days', $profile?->work_days ?? []));
        $scheduledTimeIn = old('scheduled_time_in', $profile?->scheduled_time_in ? substr((string) $profile->scheduled_time_in, 0, 5) : '');
        $scheduledTimeOut = old('scheduled_time_out', $profile?->scheduled_time_out ? substr((string) $profile->scheduled_time_out, 0, 5) : '');
        $requiredHours = old('required_hours', $profile?->required_minutes ? number_format($profile->required_minutes / 60, 2, '.', '') : ($academicTerm?->required_minutes ? number_format($academicTerm->required_minutes / 60, 2, '.', '') : ''));
    @endphp

    <form method="POST" action="{{ route('profile.update') }}" class="space-y-6">
        @csrf
        @method('PATCH')

        <section class="card card-body">
            <p class="eyebrow">Student details</p>
            <div class="mt-2"><h2 class="section-title">Your account</h2><p class="section-copy">Information used to identify your own records and reports.</p></div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div class="sm:col-span-2 lg:col-span-1"><label for="name" class="label">Full name</label><input id="name" name="name" value="{{ old('name', auth()->user()->name) }}" required autocomplete="name" @class(['input', 'input-error' => $errors->has('name')])><x-field-error name="name" /></div>
                <div><label for="student_id" class="label">Student ID</label><input id="student_id" name="student_id" value="{{ old('student_id', auth()->user()->student_id) }}" required pattern="[0-9]{7}-[12]" aria-describedby="profile-student-id-help" @class(['input', 'input-error' => $errors->has('student_id')]) placeholder="2310060-1"><p id="profile-student-id-help" class="help-text">Format: 2310060-1 or 2310060-2.</p><x-field-error name="student_id" /></div>
                <div><label for="section" class="label">Section</label><input id="section" name="section" list="academic-sections" value="{{ old('section', auth()->user()->section) }}" required @class(['input', 'input-error' => $errors->has('section')])><datalist id="academic-sections">@foreach ($academicTerm?->sections ?? [] as $section)<option value="{{ $section }}">@endforeach</datalist><x-field-error name="section" /></div>
                <div class="sm:col-span-2"><label for="program" class="label">Program / course</label><input id="program" name="program" list="academic-programs" value="{{ old('program', auth()->user()->program) }}" required @class(['input', 'input-error' => $errors->has('program')])><datalist id="academic-programs">@foreach ($academicTerm?->programs ?? [] as $program)<option value="{{ $program }}">@endforeach</datalist><x-field-error name="program" /></div>
                <div><label for="email" class="label">Email address</label><input id="email" name="email" type="email" value="{{ old('email', auth()->user()->email) }}" required autocomplete="email" @class(['input', 'input-error' => $errors->has('email')])><x-field-error name="email" /></div>
            </div>
        </section>

        <section class="card card-body">
            <p class="eyebrow">Placement</p>
            <div class="mt-2"><h2 class="section-title">Company information</h2><p class="section-copy">Your supervisor is recorded for reference only and does not receive an app account.</p></div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label for="partner_company_id" class="label">Approved partner company <span class="font-normal text-slate-400">(optional)</span></label>
                    <select id="partner_company_id" name="partner_company_id" @class(['input', 'input-error' => $errors->has('partner_company_id')])>
                        <option value="">Other / not yet assigned</option>
                        @foreach ($partnerCompanies as $partnerCompany)
                            <option value="{{ $partnerCompany->id }}" @selected((string) old('partner_company_id', $profile?->partner_company_id) === (string) $partnerCompany->id)>{{ $partnerCompany->name }}{{ $partnerCompany->is_active ? '' : ' (inactive)' }}</option>
                        @endforeach
                    </select>
                    <p class="help-text">Selecting a directory entry links your placement to the administrator-maintained partner record. Keep the fields below as the report snapshot.</p>
                    <x-field-error name="partner_company_id" />
                </div>
                <div><label for="company_name" class="label">Company name</label><input id="company_name" name="company_name" value="{{ old('company_name', $profile?->company_name) }}" @class(['input', 'input-error' => $errors->has('company_name')]) placeholder="Company or organization"><x-field-error name="company_name" /></div>
                <div><label for="department" class="label">Department</label><input id="department" name="department" value="{{ old('department', $profile?->department) }}" @class(['input', 'input-error' => $errors->has('department')]) placeholder="Department or team"><x-field-error name="department" /></div>
                <div class="sm:col-span-2"><label for="company_address" class="label">Company address</label><textarea id="company_address" name="company_address" rows="2" @class(['input', 'input-error' => $errors->has('company_address')]) placeholder="Office address">{{ old('company_address', $profile?->company_address) }}</textarea><x-field-error name="company_address" /></div>
                <div><label for="supervisor_name" class="label">Supervisor name</label><input id="supervisor_name" name="supervisor_name" value="{{ old('supervisor_name', $profile?->supervisor_name) }}" @class(['input', 'input-error' => $errors->has('supervisor_name')])><x-field-error name="supervisor_name" /></div>
                <div><label for="supervisor_phone" class="label">Supervisor contact number</label><input id="supervisor_phone" name="supervisor_phone" value="{{ old('supervisor_phone', $profile?->supervisor_phone) }}" autocomplete="tel" @class(['input', 'input-error' => $errors->has('supervisor_phone')])><x-field-error name="supervisor_phone" /></div>
                <div class="sm:col-span-2"><label for="supervisor_email" class="label">Supervisor email <span class="font-normal text-slate-400">(optional)</span></label><input id="supervisor_email" name="supervisor_email" type="email" value="{{ old('supervisor_email', $profile?->supervisor_email) }}" @class(['input', 'input-error' => $errors->has('supervisor_email')])><x-field-error name="supervisor_email" /></div>
            </div>
        </section>

        <section class="card card-body">
            <p class="eyebrow">Training plan</p>
            <div class="mt-2"><h2 class="section-title">Dates, schedule, and requirement</h2><p class="section-copy">These details are used to estimate missing workdays and remaining hours.</p></div>
            <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                <div><label for="ojt_start_date" class="label">OJT start date</label><input id="ojt_start_date" name="ojt_start_date" type="date" value="{{ old('ojt_start_date', $profile?->ojt_start_date?->format('Y-m-d') ?? $academicTerm?->starts_on?->format('Y-m-d')) }}" @class(['input', 'input-error' => $errors->has('ojt_start_date')])><x-field-error name="ojt_start_date" /></div>
                <div><label for="ojt_end_date" class="label">OJT end date</label><input id="ojt_end_date" name="ojt_end_date" type="date" value="{{ old('ojt_end_date', $profile?->ojt_end_date?->format('Y-m-d') ?? $academicTerm?->ends_on?->format('Y-m-d')) }}" @class(['input', 'input-error' => $errors->has('ojt_end_date')])><x-field-error name="ojt_end_date" /></div>
                <div><label for="scheduled_time_in" class="label">Scheduled time in</label><input id="scheduled_time_in" name="scheduled_time_in" type="time" value="{{ $scheduledTimeIn }}" @class(['input', 'input-error' => $errors->has('scheduled_time_in')])><x-field-error name="scheduled_time_in" /></div>
                <div><label for="scheduled_time_out" class="label">Scheduled time out</label><input id="scheduled_time_out" name="scheduled_time_out" type="time" value="{{ $scheduledTimeOut }}" @class(['input', 'input-error' => $errors->has('scheduled_time_out')])><x-field-error name="scheduled_time_out" /></div>
                <fieldset class="sm:col-span-2 lg:col-span-4">
                    <legend class="label">Regular workdays</legend>
                    <div class="flex flex-wrap gap-2">
                        @foreach ($weekDays as $dayNumber => $dayLabel)
                            <label class="cursor-pointer"><input type="checkbox" name="work_days[]" value="{{ $dayNumber }}" class="peer sr-only" @checked(in_array((int) $dayNumber, $selectedWorkDays, true))><span class="inline-flex rounded-xl border border-slate-300 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition peer-checked:border-teal-700 peer-checked:bg-teal-50 peer-checked:text-teal-800 peer-focus-visible:ring-4 peer-focus-visible:ring-teal-500/20">{{ $dayLabel }}</span></label>
                        @endforeach
                    </div>
                    <x-field-error name="work_days" />
                </fieldset>
                <div class="sm:col-span-2"><label for="required_hours" class="label">Required total hours</label><div class="relative"><input id="required_hours" name="required_hours" type="number" min="1" max="5000" step="0.25" value="{{ $requiredHours }}" @class(['input pr-16', 'input-error' => $errors->has('required_hours')]) placeholder="486"><span class="pointer-events-none absolute inset-y-0 right-4 flex items-center text-sm font-semibold text-slate-400">hours</span></div><x-field-error name="required_hours" /></div>
                <div class="sm:col-span-2"><label for="schedule_notes" class="label">Schedule notes <span class="font-normal text-slate-400">(optional)</span></label><textarea id="schedule_notes" name="schedule_notes" rows="3" @class(['input', 'input-error' => $errors->has('schedule_notes')]) placeholder="Example: Saturday schedule varies by assignment">{{ old('schedule_notes', $profile?->schedule_notes) }}</textarea><x-field-error name="schedule_notes" /></div>
            </div>
        </section>

        <div class="sticky bottom-20 z-20 flex justify-end rounded-2xl border border-slate-200 bg-white/95 p-3 shadow-xl backdrop-blur lg:bottom-4 print:hidden"><button type="submit" class="btn-primary w-full sm:w-auto">Save profile changes</button></div>
    </form>

    <section class="mt-6 card flex flex-col gap-4 p-5 sm:flex-row sm:items-center sm:justify-between lg:hidden">
        <div><p class="font-bold text-slate-950">App & session</p><p class="mt-1 text-sm leading-6 text-slate-500">Open installation help or securely end your session.</p></div><div class="flex gap-2"><a href="{{ route('install.help') }}" class="btn-secondary">App help</a><form method="POST" action="{{ route('logout') }}" data-logout-form>@csrf<button type="submit" class="btn-danger">Log out</button></form></div>
    </section>
@endsection
