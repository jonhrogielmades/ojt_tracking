<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <title>@yield('title', 'Administration') · {{ config('app.name', 'OJT Tracker') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased" data-authenticated="false">
    <a href="#admin-guest-main" class="sr-only z-[100] rounded-lg bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>
    <div class="relative flex min-h-screen items-center justify-center overflow-hidden px-5 py-10">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.18),transparent_35%),radial-gradient(circle_at_bottom_right,rgba(59,130,246,0.12),transparent_35%)]"></div>
        <main id="admin-guest-main" class="relative w-full max-w-md">
            <div class="mb-6 flex items-center justify-center gap-3 text-white">
                <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-11 w-11 rounded-xl shadow-lg" width="44" height="44">
                <div class="leading-tight">
                    <p class="font-bold tracking-tight">OJT Tracker</p>
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-teal-300">Administration</p>
                </div>
            </div>
            <div class="rounded-[2rem] bg-white p-6 shadow-2xl shadow-black/30 ring-1 ring-white/20 sm:p-9">
                @if (session('status'))
                    <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{{ session('status') }}</div>
                @endif
                @yield('content')
            </div>
            <p class="mt-5 text-center text-xs leading-5 text-slate-400">Restricted to authorized administrators. Student access remains separate.</p>
        </main>
    </div>
</body>
</html>
