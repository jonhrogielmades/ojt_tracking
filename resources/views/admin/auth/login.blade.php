@extends('layouts.admin-guest')

@section('title', 'Admin sign in')

@section('content')
    <p class="eyebrow">Restricted workspace</p>
    <h1 class="mt-2 text-3xl font-bold tracking-tight text-slate-950">Administrator sign in</h1>
    <p class="mt-2 text-sm leading-6 text-slate-500">Use your administrator email and password to manage student accounts.</p>

    <form method="POST" action="{{ route('admin.login.store') }}" class="mt-7 space-y-5">
        @csrf
        <div>
            <label for="email" class="label">Administrator email</label>
            <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus autocomplete="username" @class(['input', 'input-error' => $errors->has('email')]) placeholder="admin@example.com">
            <x-field-error name="email" />
        </div>
        <div>
            <div class="flex items-center justify-between gap-4">
                <label for="password" class="label">Password</label>
                <a href="{{ route('password.request') }}" class="text-xs font-bold text-teal-700 hover:text-teal-800">Forgot password?</a>
            </div>
            <input id="password" name="password" type="password" required autocomplete="current-password" @class(['input', 'input-error' => $errors->has('password')]) placeholder="Your password">
            <x-field-error name="password" />
        </div>
        <label class="flex cursor-pointer items-center gap-3 text-sm font-medium text-slate-600">
            <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500" @checked(old('remember'))>
            Keep me signed in on this device
        </label>
        <button type="submit" class="btn-primary w-full">Sign in to administration</button>
    </form>

    <p class="mt-6 text-center text-sm text-slate-600">Student account? <a href="{{ route('login') }}" class="font-bold text-teal-700 hover:text-teal-800">Return to student sign-in</a></p>
@endsection
