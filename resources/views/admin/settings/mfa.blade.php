@extends('layouts.admin')

@section('title', 'Sign-in security')
@section('eyebrow', 'Account security')
@section('page-title', 'Sign-in security')

@section('content')
    <section class="card card-body max-w-3xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div>
                <p class="eyebrow">Email verification</p>
                <h2 class="mt-2 section-title">Administrator two-step verification</h2>
                <p class="section-copy">When enabled, every password sign-in must also be confirmed with a one-time code sent to your account email.</p>
            </div>
            <span @class(['status-pill shrink-0', 'bg-emerald-100 text-emerald-800' => auth()->user()->mfa_enabled, 'bg-slate-100 text-slate-700' => ! auth()->user()->mfa_enabled])>{{ auth()->user()->mfa_enabled ? 'Enabled' : 'Disabled' }}</span>
        </div>

        <form method="POST" action="{{ route('admin.mfa.update') }}" class="mt-7 space-y-5">
            @csrf
            @method('PUT')
            <input type="hidden" name="enabled" value="{{ auth()->user()->mfa_enabled ? '0' : '1' }}">
            <div>
                <label for="current_password" class="label">Current password</label>
                <input id="current_password" name="current_password" type="password" required autocomplete="current-password" @class(['input', 'input-error' => $errors->has('current_password')])>
                <p class="help-text">Required before changing this security setting.</p>
                <x-field-error name="current_password" />
            </div>
            <button type="submit" @class(['btn-primary' => ! auth()->user()->mfa_enabled, 'btn-danger' => auth()->user()->mfa_enabled])>{{ auth()->user()->mfa_enabled ? 'Disable email verification' : 'Enable email verification' }}</button>
        </form>
    </section>
@endsection
