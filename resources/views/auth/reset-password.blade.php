@extends('layouts.guest')

@section('title', 'Choose a new password')

@section('content')
    <p class="eyebrow">Secure recovery</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Choose a new password</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Use a strong password that you do not use for another account.</p>

    <form method="POST" action="{{ route('password.update') }}" class="mt-7 space-y-5">
        @csrf
        <input type="hidden" name="token" value="{{ $token }}">
        <div>
            <label for="email" class="label">Email address</label>
            <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required autofocus autocomplete="email" @class(['input', 'input-error' => $errors->has('email')])>
            <x-field-error name="email" />
        </div>
        <div>
            <label for="password" class="label">New password</label>
            <input id="password" name="password" type="password" required autocomplete="new-password" @class(['input', 'input-error' => $errors->has('password')])>
            <x-field-error name="password" />
        </div>
        <div>
            <label for="password_confirmation" class="label">Confirm new password</label>
            <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="input">
        </div>
        <button type="submit" class="btn-primary w-full">Reset password</button>
    </form>
@endsection
