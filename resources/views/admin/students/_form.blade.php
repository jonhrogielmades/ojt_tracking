@php
    $isEditing = $student !== null;
    $isActive = (bool) old('is_active', $student?->is_active ?? true);
@endphp

<form method="POST" action="{{ $isEditing ? route('admin.students.update', $student) : route('admin.students.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <section class="card card-body">
        <p class="eyebrow">Identity</p>
        <div class="mt-2"><h2 class="section-title">Student account details</h2><p class="section-copy">Student IDs must contain seven digits, a hyphen, and a final 1 or 2.</p></div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Full name</label>
                <input id="name" name="name" value="{{ old('name', $student?->name) }}" required autofocus autocomplete="name" @class(['input', 'input-error' => $errors->has('name')]) placeholder="Juan Dela Cruz">
                <x-field-error name="name" />
            </div>
            <div>
                <label for="student_id" class="label">Student ID</label>
                <input id="student_id" name="student_id" value="{{ old('student_id', $student?->student_id) }}" required pattern="[0-9]{7}-[12]" aria-describedby="admin-student-id-help" @class(['input', 'input-error' => $errors->has('student_id')]) placeholder="2310060-1">
                <p id="admin-student-id-help" class="help-text">Example: 2310060-1 or 2310060-2.</p>
                <x-field-error name="student_id" />
            </div>
            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $student?->email) }}" required autocomplete="email" @class(['input', 'input-error' => $errors->has('email')]) placeholder="student@example.com">
                <x-field-error name="email" />
            </div>
            <div>
                <label for="program" class="label">Program / course</label>
                <input id="program" name="program" value="{{ old('program', $student?->program) }}" required @class(['input', 'input-error' => $errors->has('program')]) placeholder="BS Information Technology">
                <x-field-error name="program" />
            </div>
            <div>
                <label for="section" class="label">Section</label>
                <input id="section" name="section" value="{{ old('section', $student?->section) }}" required @class(['input', 'input-error' => $errors->has('section')]) placeholder="BSIT 4A">
                <x-field-error name="section" />
            </div>
        </div>
    </section>

    <section class="card card-body">
        <p class="eyebrow">Security & access</p>
        <div class="mt-2"><h2 class="section-title">Password and account status</h2><p class="section-copy">{{ $isEditing ? 'Leave the password fields blank to keep the current password.' : 'Set the initial password the student will use to sign in.' }}</p></div>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="password" class="label">{{ $isEditing ? 'New password' : 'Password' }}</label>
                <input id="password" name="password" type="password" @required(! $isEditing) autocomplete="new-password" @class(['input', 'input-error' => $errors->has('password')]) placeholder="{{ $isEditing ? 'Leave blank to keep current password' : 'At least 8 characters' }}">
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm {{ $isEditing ? 'new ' : '' }}password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEditing) autocomplete="new-password" class="input" placeholder="Repeat the password">
            </div>
            <div class="sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500" @checked($isActive)>
                    <span><span class="block text-sm font-bold text-slate-900">Active student account</span><span class="mt-1 block text-xs leading-5 text-slate-500">Inactive students cannot sign in, but their existing OJT records remain safely stored.</span></span>
                </label>
                <x-field-error name="is_active" />
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ $isEditing ? route('admin.students.show', $student) : route('admin.students.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $isEditing ? 'Save student changes' : 'Create student account' }}</button>
    </div>
</form>
