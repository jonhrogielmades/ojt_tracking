@extends('layouts.admin-guest')

@section('title', 'Verify administrator sign-in')

@section('content')
    <p class="eyebrow">Two-step verification</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Check your email</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Enter the six-digit code sent to <strong class="font-bold text-slate-700">{{ $maskedEmail }}</strong>. Codes expire after a short time and work only once.</p>

    <form method="POST" action="{{ route('admin.mfa.verify') }}" class="mt-7 space-y-5">
        @csrf
        <div>
            <label for="code" class="label">Verification code</label>
            <input id="code" name="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6" required autofocus autocomplete="one-time-code" aria-describedby="mfa-code-help" @class(['input text-center font-mono text-2xl tracking-[0.45em]', 'input-error' => $errors->has('code')]) placeholder="000000">
            <p id="mfa-code-help" class="help-text">Six digits from the latest verification email.</p>
            <x-field-error name="code" />
        </div>
        <button type="submit" class="btn-primary w-full">Verify and continue</button>
    </form>

    <form method="POST" action="{{ route('admin.mfa.resend') }}" class="mt-4 text-center">
        @csrf
        <button type="submit" class="text-sm font-bold text-teal-700 hover:text-teal-800">Send a new code</button>
    </form>
@endsection
