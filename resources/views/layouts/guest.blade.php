<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <title>@yield('title', 'Welcome') · {{ config('app.name', 'OJT Tracker') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-slate-950 text-slate-900 antialiased" data-authenticated="false">
    <div class="relative min-h-screen overflow-hidden">
        <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_top_left,rgba(20,184,166,0.22),transparent_34%),radial-gradient(circle_at_bottom_right,rgba(56,189,248,0.12),transparent_32%)]"></div>
        <div class="relative mx-auto grid min-h-screen max-w-7xl items-center gap-12 px-5 py-10 lg:grid-cols-[1.05fr_0.95fr] lg:px-10">
            <section class="hidden text-white lg:block">
                <x-app-logo class="[&_span_span:first-child]:text-white [&_span_span:last-child]:text-teal-300" />
                <p class="mt-12 text-sm font-semibold uppercase tracking-[0.25em] text-teal-300">Your OJT, clearly tracked</p>
                <h1 class="mt-4 max-w-2xl text-5xl font-bold leading-[1.08] tracking-tight">Hours, accomplishments, and reports in one dependable place.</h1>
                <p class="mt-6 max-w-xl text-lg leading-8 text-slate-300">Record daily attendance, capture what you learned, and stay on top of required training hours—even when your connection drops.</p>
                <div class="mt-10 grid max-w-xl grid-cols-3 gap-4 text-sm">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-2xl text-white">DTR</strong><span class="text-slate-400">Official hours</span></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-2xl text-white">Offline</strong><span class="text-slate-400">Draft safely</span></div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4"><strong class="block text-2xl text-white">Reports</strong><span class="text-slate-400">Print ready</span></div>
                </div>
            </section>
            <main class="mx-auto w-full max-w-lg">
                <div class="mb-6 flex justify-center lg:hidden"><x-app-logo class="[&_span_span:first-child]:text-white [&_span_span:last-child]:text-teal-300" /></div>
                <div class="rounded-[2rem] bg-white p-6 shadow-2xl shadow-black/30 ring-1 ring-white/30 sm:p-9">
                    @if (session('status'))
                        <div class="mb-6 rounded-xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-medium text-teal-800">{{ session('status') }}</div>
                    @endif
                    @yield('content')
                </div>
                <p class="mt-5 text-center text-xs leading-5 text-slate-400">Student records stay private. Administrator access uses a separate, protected workspace.</p>
            </main>
        </div>
    </div>
</body>
</html>
