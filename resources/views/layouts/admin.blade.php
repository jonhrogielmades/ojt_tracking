<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0f172a">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="OJT Tracker administrator workspace">
    <link rel="icon" href="{{ asset('icons/icon-192.png') }}">
    <title>@yield('title', 'Administration') · {{ config('app.name', 'OJT Tracker') }}</title>
    @fonts
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('head')
</head>
<body class="min-h-screen bg-slate-100 text-slate-900 antialiased" data-authenticated="true">
    <a href="#admin-main-content" class="sr-only z-[100] rounded-lg bg-white px-4 py-2 focus:not-sr-only focus:fixed focus:left-4 focus:top-4">Skip to content</a>

    @php
        $adminUnreadNotificationCount = auth()->user()?->unreadNotifications()->count() ?? 0;
    @endphp

    <aside class="fixed inset-y-0 left-0 z-40 hidden w-64 flex-col border-r border-slate-800 bg-slate-950 text-slate-300 lg:flex">
        <div class="flex items-center gap-3 px-6 py-6">
            <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-10 w-10 rounded-xl shadow-sm" width="40" height="40">
            <div class="min-w-0 leading-tight">
                <p class="truncate text-base font-bold tracking-tight text-white">OJT Tracker</p>
                <p class="truncate text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-teal-300">Administration</p>
            </div>
        </div>

        <nav aria-label="Admin navigation" class="flex-1 space-y-1 overflow-y-auto px-3 pb-4">
            <a href="{{ route('admin.dashboard') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.dashboard')])>
                <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Overview
            </a>
            <a href="{{ route('admin.alerts.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.alerts.*')])>
                <span class="h-2 w-2 rounded-full bg-current opacity-60"></span><span class="min-w-0 flex-1">Notification center</span>
                @if ($adminUnreadNotificationCount > 0)<span class="rounded-full bg-amber-300 px-1.5 py-0.5 text-[0.65rem] font-black text-slate-950">{{ min($adminUnreadNotificationCount, 99) }}</span>@endif
            </a>
            @if (auth()->user()?->hasCapability('students.view'))
                <a href="{{ route('admin.students.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.students.*')])>
                    <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Student accounts
                </a>
            @endif
            @if (auth()->user()?->hasCapability('approvals.manage') || auth()->user()?->hasCapability('risk.view'))
                <p class="px-3 pb-1 pt-4 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-500">Workflow</p>
            @endif
            @if (auth()->user()?->hasCapability('approvals.manage'))
                <a href="{{ route('admin.approval_queue') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.approval_queue')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Approval queue</a>
            @endif
            @if (auth()->user()?->hasCapability('risk.view'))
                <a href="{{ route('admin.risk_dashboard') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.risk_dashboard')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>At-risk students</a>
            @endif
            @if (auth()->user()?->hasCapability('academic.manage') || auth()->user()?->hasCapability('companies.manage') || auth()->user()?->hasCapability('documents.manage') || auth()->user()?->hasCapability('reports.manage') || auth()->user()?->hasCapability('announcements.manage') || auth()->user()?->hasCapability('students.manage'))
                <p class="px-3 pb-1 pt-4 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-500">Operations</p>
            @endif
            @if (auth()->user()?->hasCapability('academic.manage'))
                <a href="{{ route('admin.academic_terms.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.academic_terms.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Academic terms</a>
            @endif
            @if (auth()->user()?->hasCapability('companies.manage'))
                <a href="{{ route('admin.partner_companies.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.partner_companies.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Partner companies</a>
            @endif
            @if (auth()->user()?->hasCapability('documents.manage'))
                <a href="{{ route('admin.document_reviews.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.document_reviews.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Document reviews</a>
                <a href="{{ route('admin.document_requirements.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.document_requirements.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Document requirements</a>
            @endif
            @if (auth()->user()?->hasCapability('students.manage'))
                <a href="{{ route('admin.students.import') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.students.import*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Student import</a>
            @endif
            @if (auth()->user()?->hasCapability('reports.view'))
                <a href="{{ route('admin.students.export') }}" class="nav-link"><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Student export</a>
            @endif
            @if (auth()->user()?->hasCapability('reports.manage'))
                <a href="{{ route('admin.report_templates.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.report_templates.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Report templates</a>
            @endif
            @if (auth()->user()?->hasCapability('announcements.manage'))
                <a href="{{ route('admin.announcements.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.announcements.*')])><span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Announcements</a>
            @endif
            <p class="px-3 pb-1 pt-4 text-[0.62rem] font-bold uppercase tracking-[0.2em] text-slate-500">Governance</p>
            @if (auth()->user()?->hasCapability('administrators.manage'))
                <a href="{{ route('admin.administrators.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.administrators.*')])>
                    <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Administrators
                </a>
            @endif
            @if (auth()->user()?->hasCapability('audit.view'))
                <a href="{{ route('admin.audit.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.audit.*')])>
                    <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Audit trail
                </a>
            @endif
            @if (auth()->user()?->hasCapability('backups.manage'))
                <a href="{{ route('admin.backups.index') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.backups.*')])>
                    <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Database backups
                </a>
            @endif
            @if (auth()->user()?->hasCapability('system-health.view'))
                <a href="{{ route('admin.system-health') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.system-health')])>
                    <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>System health
                </a>
            @endif
            <a href="{{ route('admin.mfa.edit') }}" @class(['nav-link', 'nav-link-active' => request()->routeIs('admin.mfa.edit')])>
                <span class="h-2 w-2 rounded-full bg-current opacity-60"></span>Sign-in security
            </a>
        </nav>

        <div class="space-y-3 border-t border-slate-800 p-4">
            <fieldset class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                <legend class="px-1 text-[0.65rem] font-bold uppercase tracking-wider text-slate-400">Accessibility</legend>
                <label class="mt-1 flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-300">
                    <input type="checkbox" data-accessibility-large-text class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-teal-500 focus:ring-teal-400">
                    Larger text
                </label>
                <label class="mt-2 flex cursor-pointer items-center gap-2 text-xs font-semibold text-slate-300">
                    <input type="checkbox" data-accessibility-high-contrast class="h-4 w-4 rounded border-slate-600 bg-slate-950 text-teal-500 focus:ring-teal-400">
                    Higher contrast
                </label>
            </fieldset>
            <div class="rounded-xl border border-slate-800 bg-slate-900/70 p-3">
                <p class="truncate text-sm font-semibold text-white">{{ auth()->user()?->name }}</p>
                <p class="mt-0.5 truncate text-xs text-slate-500">{{ auth()->user()?->email }}</p>
                <span class="mt-2 inline-flex rounded-full bg-teal-400/10 px-2 py-1 text-[0.65rem] font-bold uppercase tracking-wider text-teal-300">{{ auth()->user()?->role->label() }}</span>
            </div>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full rounded-xl border border-slate-700 px-3 py-2 text-sm font-bold text-slate-300 transition hover:border-slate-600 hover:bg-slate-900 hover:text-white">Log out</button>
            </form>
        </div>
    </aside>

    <div class="min-h-screen lg:pl-64">
        <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex min-h-16 max-w-[90rem] items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <div class="flex min-w-0 items-center gap-3">
                    <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-9 w-9 rounded-lg lg:hidden" width="36" height="36">
                    <div class="min-w-0">
                        <p class="truncate text-sm font-semibold text-slate-500">@yield('eyebrow', 'Administration')</p>
                        <h1 class="truncate text-lg font-bold tracking-tight text-slate-950 sm:text-xl">@yield('page-title', 'Overview')</h1>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    @if (auth()->user()?->hasCapability('students.view'))
                        <a href="{{ route('admin.students.index') }}" class="btn-ghost px-3 lg:hidden">Students</a>
                    @endif
                    @can('create', App\Models\User::class)
                        <a href="{{ route('admin.students.create') }}" class="btn-primary">Add student</a>
                    @endcan
                </div>
            </div>
        </header>

        <main id="admin-main-content" class="mx-auto max-w-[90rem] px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
            <nav aria-label="Mobile admin navigation" class="mb-5 flex flex-wrap gap-2 lg:hidden">
                <a href="{{ route('admin.dashboard') }}" @class(['rounded-xl border px-4 py-2.5 text-center text-sm font-bold', 'border-teal-200 bg-teal-50 text-teal-800' => request()->routeIs('admin.dashboard'), 'border-slate-200 bg-white text-slate-600' => ! request()->routeIs('admin.dashboard')])>Overview</a>
                <a href="{{ route('admin.alerts.index') }}" @class(['rounded-xl border px-4 py-2.5 text-center text-sm font-bold', 'border-teal-200 bg-teal-50 text-teal-800' => request()->routeIs('admin.alerts.*'), 'border-slate-200 bg-white text-slate-600' => ! request()->routeIs('admin.alerts.*')])>
                    Notifications
                    @if ($adminUnreadNotificationCount > 0)
                        ({{ min($adminUnreadNotificationCount, 99) }})
                    @endif
                </a>
                @if (auth()->user()?->hasCapability('students.view'))
                    <a href="{{ route('admin.students.index') }}" @class(['rounded-xl border px-4 py-2.5 text-center text-sm font-bold', 'border-teal-200 bg-teal-50 text-teal-800' => request()->routeIs('admin.students.*'), 'border-slate-200 bg-white text-slate-600' => ! request()->routeIs('admin.students.*')])>Students</a>
                @endif
                @if (auth()->user()?->hasCapability('administrators.manage'))
                    <a href="{{ route('admin.administrators.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-600">Administrators</a>
                @endif
                @if (auth()->user()?->hasCapability('audit.view'))
                    <a href="{{ route('admin.audit.index') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-600">Audit</a>
                @endif
                <a href="{{ route('admin.mfa.edit') }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-center text-sm font-bold text-slate-600">Security</a>
            </nav>

            <details class="mb-5 rounded-xl border border-slate-200 bg-white p-4 lg:hidden">
                <summary class="cursor-pointer text-sm font-bold text-slate-800">Operations & governance</summary>
                <div class="mt-3 flex flex-wrap gap-2 text-sm font-bold">
                    @if (auth()->user()?->hasCapability('approvals.manage'))<a href="{{ route('admin.approval_queue') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Approvals</a>@endif
                    @if (auth()->user()?->hasCapability('risk.view'))<a href="{{ route('admin.risk_dashboard') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">At-risk</a>@endif
                    @if (auth()->user()?->hasCapability('academic.manage'))<a href="{{ route('admin.academic_terms.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Terms</a>@endif
                    @if (auth()->user()?->hasCapability('companies.manage'))<a href="{{ route('admin.partner_companies.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Companies</a>@endif
                    @if (auth()->user()?->hasCapability('documents.manage'))<a href="{{ route('admin.document_reviews.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Reviews</a><a href="{{ route('admin.document_requirements.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Requirements</a>@endif
                    @if (auth()->user()?->hasCapability('students.manage'))<a href="{{ route('admin.students.import') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Import</a>@endif
                    @if (auth()->user()?->hasCapability('reports.view'))<a href="{{ route('admin.students.export') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Export</a>@endif
                    @if (auth()->user()?->hasCapability('reports.manage'))<a href="{{ route('admin.report_templates.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Templates</a>@endif
                    @if (auth()->user()?->hasCapability('announcements.manage'))<a href="{{ route('admin.announcements.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Announcements</a>@endif
                    @if (auth()->user()?->hasCapability('backups.manage'))<a href="{{ route('admin.backups.index') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Backups</a>@endif
                    @if (auth()->user()?->hasCapability('system-health.view'))<a href="{{ route('admin.system-health') }}" class="rounded-lg bg-slate-100 px-3 py-2 text-slate-700">Health</a>@endif
                </div>
            </details>

            <details class="mb-5 rounded-xl border border-slate-200 bg-white p-4 lg:hidden">
                <summary class="cursor-pointer text-sm font-bold text-slate-800">Accessibility preferences</summary>
                <div class="mt-3 flex flex-wrap gap-4">
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" data-accessibility-large-text class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500"> Larger text</label>
                    <label class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700"><input type="checkbox" data-accessibility-high-contrast class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500"> Higher contrast</label>
                </div>
            </details>

            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800" role="status">{{ session('success') }}</div>
            @endif
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-teal-200 bg-teal-50 px-4 py-3 text-sm font-semibold text-teal-800" role="status">{{ session('status') }}</div>
            @endif
            @if ($errors->any())
                <div class="mb-6 rounded-2xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-800" role="alert">
                    <p class="font-bold">Please review the highlighted fields.</p>
                    <p class="mt-1">{{ $errors->first() }}</p>
                </div>
            @endif

            @yield('content')
        </main>
    </div>

    @stack('scripts')
</body>
</html>
