@extends('layouts.guest')

@section('title', 'Sign in')

@section('content')
    <p class="eyebrow">Welcome back</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Sign in to your tracker</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Use your Student ID and password to continue recording your OJT progress.</p>

    <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
        @csrf
        <div>
            <label for="student_id" class="label">Student ID</label>
            <input id="student_id" name="student_id" type="text" value="{{ old('student_id') }}" required autofocus autocomplete="username" pattern="[0-9]{7}-[12]" aria-describedby="student-id-help" @class(['input', 'input-error' => $errors->has('student_id')]) placeholder="2310060-1">
            <p id="student-id-help" class="help-text">Enter seven digits, a hyphen, then 1 or 2.</p>
            <x-field-error name="student_id" />
        </div>
        <div>
            <div class="flex items-center justify-between gap-4"><label for="password" class="label">Password</label><a href="{{ route('password.request') }}" class="text-xs font-bold text-teal-700 hover:text-teal-800">Forgot password?</a></div>
            <input id="password" name="password" type="password" required autocomplete="current-password" @class(['input', 'input-error' => $errors->has('password')]) placeholder="Your password">
            <x-field-error name="password" />
        </div>
        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-slate-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500" @checked(old('remember'))>
            Keep me signed in on this device
        </label>
        <button type="submit" class="btn-primary w-full">Sign in</button>
    </form>

    <div class="mt-6 grid gap-2 text-center text-sm text-slate-600">
        <p>New student? <a href="{{ route('register') }}" class="font-bold text-teal-700 hover:text-teal-800">Create an account</a></p>
        <p class="text-xs text-slate-500">Administrator? <a href="{{ route('admin.login') }}" class="font-bold text-slate-700 hover:text-slate-950">Open the admin sign-in</a></p>
    </div>
@endsection
