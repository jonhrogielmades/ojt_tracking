@extends('layouts.guest')

@section('title', 'Reset password')

@section('content')
    <p class="eyebrow">Account recovery</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Reset your password</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Enter the email address on your student or administrator account. We will send a secure, time-limited reset link if it matches an account.</p>

    <form method="POST" action="{{ route('password.email') }}" class="mt-7 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="email" @class(['input', 'input-error' => $errors->has('email')]) placeholder="you@example.com">
            <x-field-error name="email" />
        </div>
        <button type="submit" class="btn-primary w-full">Send password reset link</button>
    </form>

    <div class="mt-6 flex flex-wrap justify-center gap-x-4 gap-y-2 text-sm">
        <a href="{{ route('login') }}" class="font-bold text-teal-700 hover:text-teal-800">Student sign-in</a>
        <a href="{{ route('admin.login') }}" class="font-bold text-slate-700 hover:text-slate-950">Administrator sign-in</a>
    </div>
@endsection
