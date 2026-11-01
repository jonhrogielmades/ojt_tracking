@extends('layouts.guest')

@section('title', 'Create account')

@section('content')
    <p class="eyebrow">Student account</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Start tracking your OJT</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">One private workspace for your attendance, activities, documents, and reports.</p>

    <form method="POST" action="{{ route('register') }}" class="mt-7 space-y-5">
        @csrf
        <div class="grid gap-5 sm:grid-cols-2">
            <div class="sm:col-span-2">
                <label for="name" class="label">Full name</label>
                <input id="name" name="name" value="{{ old('name') }}" required autofocus autocomplete="name" @class(['input', 'input-error' => $errors->has('name')]) placeholder="Juan Dela Cruz">
                <x-field-error name="name" />
            </div>
            <div>
                <label for="student_id" class="label">Student ID</label>
                <input id="student_id" name="student_id" value="{{ old('student_id') }}" required autocomplete="off" pattern="[0-9]{7}-[12]" aria-describedby="student-id-help" @class(['input', 'input-error' => $errors->has('student_id')]) placeholder="2310060-1">
                <p id="student-id-help" class="help-text">Required format: seven digits, a hyphen, then 1 or 2.</p>
                <x-field-error name="student_id" />
            </div>
            <div>
                <label for="section" class="label">Section</label>
                <input id="section" name="section" value="{{ old('section') }}" required @class(['input', 'input-error' => $errors->has('section')]) placeholder="BSIT 4A">
                <x-field-error name="section" />
            </div>
            <div class="sm:col-span-2">
                <label for="program" class="label">Program / course</label>
                <input id="program" name="program" value="{{ old('program') }}" required @class(['input', 'input-error' => $errors->has('program')]) placeholder="Bachelor of Science in Information Technology">
                <x-field-error name="program" />
            </div>
            <div class="sm:col-span-2">
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" @class(['input', 'input-error' => $errors->has('email')]) placeholder="you@example.com">
                <x-field-error name="email" />
            </div>
            <div>
                <label for="password" class="label">Password</label>
                <input id="password" name="password" type="password" required autocomplete="new-password" @class(['input', 'input-error' => $errors->has('password')]) placeholder="At least 8 characters">
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input" placeholder="Repeat password">
            </div>
        </div>
        <p class="help-text">This form creates a student account only. Administrator accounts use a separate protected sign-in and cannot be created here.</p>
        <button type="submit" class="btn-primary w-full">Create student account</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">Already registered? <a href="{{ route('login') }}" class="font-bold text-teal-700 hover:text-teal-800">Sign in</a></p>
@endsection
