<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f766e">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="Student-only OJT attendance, activity, and report tracker">
    <link rel="manifest" href="{{ asset('manifest.webmanifest') }}">
    <link rel="apple-touch-icon" href="{{ asset('icons/icon-192.png') }}">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <title>@yield('title', 'Dashboard') · {{ config('app.name', 'OJT Tracker') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased" data-authenticated="true" data-student-key="{{ auth()->user()?->offline_storage_key }}">
    <a href="#main-content" class="sr-only z-[100] rounded-lg bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    @php
        $unreadNotificationCount = auth()->user()?->unreadNotifications()->count() ?? 0;
        $navigation = [
            ['route' => 'dashboard', 'label' => 'Dashboard', 'pattern' => 'dashboard'],
            ['route' => 'announcements.index', 'label' => 'Announcements', 'pattern' => 'announcements.*'],
            ['route' => 'dtr.index', 'label' => 'Daily time record', 'pattern' => 'dtr.*'],
            ['route' => 'activities.index', 'label' => 'Activity log', 'pattern' => 'activities.*'],
            ['route' => 'submissions.index', 'label' => 'Weekly submissions', 'pattern' => 'submissions.*'],
            ['route' => 'student.calendar', 'label' => 'Calendar', 'pattern' => 'student.calendar'],
            ['route' => 'student.progress', 'label' => 'Progress forecast', 'pattern' => 'student.progress*'],
            ['route' => 'student.portfolio', 'label' => 'Skills portfolio', 'pattern' => 'student.portfolio*'],
            ['route' => 'reports.monthly', 'label' => 'Reports', 'pattern' => 'reports.*'],
            ['route' => 'documents.index', 'label' => 'Documents', 'pattern' => 'documents.*'],
            ['route' => 'student.notifications', 'label' => 'Feedback & notifications', 'pattern' => 'student.notifications*'],
            ['route' => 'profile.edit', 'label' => 'Profile & placement', 'pattern' => 'profile.*'],
        ];
    @endphp

    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-slate-800 bg-slate-950 text-slate-300 lg:flex print:hidden">
        <div class="px-6 py-6"><x-app-logo class="[&_span_span:first-child]:text-white [&_span_span:last-child]:text-teal-300" /></div>
        <nav aria-label="Primary" class="min-h-0 flex-1 space-y-1 overflow-y-auto px-3 pb-3">
            @foreach ($navigation as $item)
                <a href="{{ route($item['route']) }}" @class(['nav-link', 'nav-link-active' => request()->routeIs($item['pattern'])])>
                    <span class="h-2 w-2 shrink-0 rounded-full bg-current opacity-60"></span>
                    <span class="min-w-0 flex-1">{{ $item['label'] }}</span>
                    @if ($item['route'] === 'student.notifications' && $unreadNotificationCount > 0)
                        <span class="rounded-full bg-amber-300 px-1.5 py-0.5 text-[0.65rem] font-black text-slate-950">{{ min($unreadNotificationCount, 99) }}</span>
                    @endif
                </a>
            @endforeach
        </nav>
        <div class="space-y-3 border-t border-slate-800 p-4">
            <details class="rounded-xl border border-slate-800 bg-slate-900/70 text-sm">
                <summary class="min-h-11 cursor-pointer list-none px-3 py-3 font-semibold text-slate-300">Accessibility</summary>
                <div class="space-y-3 border-t border-slate-800 px-3 py-3">
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 text-slate-300">
                        <input type="checkbox" data-accessibility-large-text class="h-5 w-5 rounded border-slate-500 text-teal-600 focus:ring-teal-500">
                        <span>Larger text</span>
                    </label>
                    <label class="flex min-h-11 cursor-pointer items-center gap-3 text-slate-300">
                        <input type="checkbox" data-accessibility-high-contrast class="h-5 w-5 rounded border-slate-500 text-teal-600 focus:ring-teal-500">
                        <span>Higher contrast</span>
                    </label>
                </div>
            </details>
            <button type="button" data-install-button hidden class="w-full rounded-xl border border-slate-700 px-3 py-2 text-left text-sm font-semibold text-slate-200 hover:bg-slate-900">Install app</button>
            <a href="{{ route('install.help') }}" class="block text-xs font-medium text-slate-400 hover:text-white">Installation & offline help</a>
            <div class="flex items-center justify-between gap-3">
                <div class="min-w-0"><p class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name }}</p><p class="truncate text-xs text-slate-500">{{ auth()->user()?->student_id }}</p></div>
                <form method="POST" action="{{ route('logout') }}" data-logout-form>@csrf<button type="submit" class="text-xs font-bold text-slate-400 hover:text-white">Log out</button></form>
            </div>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-64">
        <header aria-label="Student top bar" class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur print:hidden">
            <div class="mx-auto flex min-h-14 max-w-[90rem] items-center gap-2 px-3 py-1.5 sm:min-h-16 sm:gap-4 sm:px-6 sm:py-0 lg:px-8">
                <div class="shrink-0 lg:hidden"><x-app-logo compact class="[&_img]:h-9 [&_img]:w-9" /></div>
                <div class="min-w-0 flex-1">
                    <p class="hidden truncate text-sm font-semibold text-slate-500 sm:block">@yield('eyebrow', 'Student workspace')</p>
                    <h1 class="truncate text-base font-bold tracking-tight text-slate-950 sm:text-xl">@yield('page-title', 'Dashboard')</h1>
                </div>
                <div class="flex shrink-0 items-center gap-1 sm:gap-2">
                    <details class="relative lg:hidden">
                        <summary class="flex h-11 w-11 cursor-pointer list-none items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-teal-300 hover:text-teal-700" aria-label="Accessibility options" title="Accessibility options"><x-navigation-icon name="accessibility" /></summary>
                        <div class="fixed inset-x-3 top-16 z-50 space-y-2 rounded-2xl border border-slate-200 bg-white p-3 shadow-xl sm:absolute sm:inset-x-auto sm:right-0 sm:top-auto sm:mt-2 sm:w-56">
                            <p class="text-xs font-bold uppercase tracking-wider text-slate-500">Accessibility</p>
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700">
                                <input type="checkbox" data-accessibility-large-text class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                                <span>Larger text</span>
                            </label>
                            <label class="flex min-h-11 cursor-pointer items-center gap-3 text-sm font-semibold text-slate-700">
                                <input type="checkbox" data-accessibility-high-contrast class="h-5 w-5 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                                <span>Higher contrast</span>
                            </label>
                        </div>
                    </details>
                    <span id="connection-status" role="status" aria-live="polite" class="status-pill min-h-11 min-w-11 justify-center bg-slate-100 px-0 text-slate-600 sm:min-w-0 sm:px-2.5"><span class="h-2 w-2 rounded-full bg-slate-400"></span><span class="sr-only sm:not-sr-only">Checking</span></span>
                    <button type="button" data-draft-panel-open class="relative flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm transition hover:border-teal-300 hover:text-teal-700 sm:w-auto sm:gap-2 sm:px-3" aria-label="Open pending drafts" title="Pending drafts">
                        <x-navigation-icon name="drafts" />
                        <span class="hidden text-xs font-bold sm:inline">Pending</span>
                        <span id="pending-count" class="absolute -right-1 -top-1 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-amber-100 px-1 text-[0.6rem] font-black leading-none text-amber-900 ring-2 ring-white sm:static sm:min-h-0 sm:min-w-0 sm:px-1.5 sm:py-0.5 sm:text-xs sm:ring-0">0</span>
                    </button>
                </div>
            </div>
        </header>

        <main id="main-content" class="mx-auto max-w-[90rem] px-4 py-6 pb-28 sm:px-6 lg:px-8 lg:pb-10">
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <p class="font-bold">Please review the highlighted fields.</p><p class="mt-1">{{ $errors->first() }}</p>
                </div>
            @endif
            @yield('content')
        </main>
    </div>

    <nav aria-label="Mobile navigation" class="fixed inset-x-0 bottom-0 z-30 grid grid-cols-5 border-t border-slate-200 bg-white/95 px-1 pb-[max(.5rem,env(safe-area-inset-bottom))] pt-1.5 shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden print:hidden">
        <a href="{{ route('dashboard') }}" @class(['mobile-nav-link group', 'mobile-nav-link-active' => request()->routeIs('dashboard')]) @if (request()->routeIs('dashboard')) aria-current="page" @endif>
            <span class="mobile-nav-icon"><x-navigation-icon name="home" /></span>
            <span>Home</span>
        </a>
        <a href="{{ route('dtr.index') }}" @class(['mobile-nav-link group', 'mobile-nav-link-active' => request()->routeIs('dtr.*')]) @if (request()->routeIs('dtr.*')) aria-current="page" @endif>
            <span class="mobile-nav-icon"><x-navigation-icon name="clock" /></span>
            <span>DTR</span>
        </a>
        <a href="{{ route('activities.index') }}" @class(['mobile-nav-link group', 'mobile-nav-link-active' => request()->routeIs('activities.*')]) @if (request()->routeIs('activities.*')) aria-current="page" @endif>
            <span class="mobile-nav-icon"><x-navigation-icon name="logs" /></span>
            <span>Logs</span>
        </a>
        <a href="{{ route('submissions.index') }}" @class(['mobile-nav-link group', 'mobile-nav-link-active' => request()->routeIs('submissions.*')]) @if (request()->routeIs('submissions.*')) aria-current="page" @endif>
            <span class="mobile-nav-icon"><x-navigation-icon name="submit" /></span>
            <span>Submit</span>
        </a>
        <details class="group relative">
            <summary @class(['mobile-nav-link cursor-pointer list-none', 'mobile-nav-link-active' => request()->routeIs('student.*', 'announcements.*', 'reports.*', 'documents.*', 'profile.*', 'corrections.*')]) @if (request()->routeIs('student.*', 'announcements.*', 'reports.*', 'documents.*', 'profile.*', 'corrections.*')) aria-current="page" @endif>
                <span class="mobile-nav-icon">
                    <x-navigation-icon name="more" />
                    @if ($unreadNotificationCount > 0)
                        <span class="absolute -right-0.5 -top-0.5 h-2.5 w-2.5 rounded-full bg-amber-500 ring-2 ring-white"><span class="sr-only">{{ $unreadNotificationCount }} unread notifications</span></span>
                    @endif
                </span>
                <span>More</span>
            </summary>
            <div class="fixed inset-x-3 bottom-[calc(4.75rem+env(safe-area-inset-bottom))] max-h-[min(70dvh,32rem)] overflow-y-auto rounded-3xl border border-slate-200 bg-white p-2 shadow-2xl">
                <div class="px-3 pb-2 pt-1"><p class="text-xs font-black uppercase tracking-[0.16em] text-slate-400">More tools</p></div>
                <a href="{{ route('announcements.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('announcements.*')]) @if (request()->routeIs('announcements.*')) aria-current="page" @endif>Announcements</a>
                <a href="{{ route('student.notifications') }}" @class(['mobile-menu-link justify-between', 'mobile-menu-link-active' => request()->routeIs('student.notifications*')]) @if (request()->routeIs('student.notifications*')) aria-current="page" @endif><span>Feedback & notifications</span>@if ($unreadNotificationCount > 0)<span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-900">{{ min($unreadNotificationCount, 99) }}</span>@endif</a>
                <a href="{{ route('student.calendar') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('student.calendar')]) @if (request()->routeIs('student.calendar')) aria-current="page" @endif>Calendar</a>
                <a href="{{ route('student.progress') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('student.progress')]) @if (request()->routeIs('student.progress')) aria-current="page" @endif>Progress forecast</a>
                <a href="{{ route('student.portfolio') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('student.portfolio')]) @if (request()->routeIs('student.portfolio')) aria-current="page" @endif>Skills portfolio</a>
                <a href="{{ route('reports.monthly') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('reports.*')]) @if (request()->routeIs('reports.*')) aria-current="page" @endif>Reports</a>
                <a href="{{ route('documents.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('documents.*')]) @if (request()->routeIs('documents.*')) aria-current="page" @endif>Documents</a>
                <a href="{{ route('profile.edit') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('profile.*')]) @if (request()->routeIs('profile.*')) aria-current="page" @endif>Profile & placement</a>
            </div>
        </details>
    </nav>

    <div data-draft-panel-backdrop hidden class="fixed inset-0 z-50 bg-slate-950/40 backdrop-blur-sm print:hidden"></div>
    <aside data-draft-panel hidden aria-label="Offline drafts" class="fixed inset-y-0 right-0 z-[60] w-full max-w-md overflow-y-auto bg-white p-5 shadow-2xl print:hidden">
        <div class="flex items-start justify-between gap-4"><div><p class="text-xs font-bold uppercase tracking-[0.18em] text-teal-700">Offline queue</p><h2 class="mt-1 text-xl font-bold text-slate-950">Pending drafts</h2><p class="mt-1 text-sm text-slate-500">Drafts stay on this device until synced or removed.</p></div><button type="button" data-draft-panel-close class="rounded-lg p-2 text-slate-500 hover:bg-slate-100" aria-label="Close">✕</button></div>
        <div class="mt-5 flex gap-2"><button type="button" data-sync-now class="btn-primary flex-1">Sync now</button><button type="button" data-retry-conflicts class="btn-secondary">Retry all</button></div>
        <div id="draft-list" class="mt-5 space-y-3"></div>
    </aside>

    <div id="logout-modal" hidden class="fixed inset-0 z-[70] place-items-center bg-slate-950/60 p-4 backdrop-blur-sm print:hidden" role="dialog" aria-modal="true" aria-labelledby="logout-title">
        <div class="w-full max-w-md rounded-3xl bg-white p-6 shadow-2xl">
            <h2 id="logout-title" class="text-xl font-bold text-slate-950">Pending drafts on this device</h2>
            <p class="mt-2 text-sm leading-6 text-slate-600">Sync them before logging out, or permanently delete them so the next student using this device cannot see them.</p>
            <div class="mt-6 grid gap-2"><button type="button" data-logout-sync class="btn-primary">Sync, then log out</button><button type="button" data-logout-delete class="btn-danger">Delete drafts and log out</button><button type="button" data-logout-cancel class="btn-secondary">Cancel</button></div>
        </div>
    </div>

    <div id="app-toast" hidden class="fixed bottom-24 right-4 z-[80] max-w-sm rounded-2xl bg-slate-950 px-4 py-3 text-sm font-semibold text-white shadow-xl lg:bottom-6 print:hidden" role="status" aria-live="polite"></div>
    @stack('scripts')
</body>
</html>
