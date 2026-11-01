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
        $adminStudentsNavigationIsActive = request()->routeIs(
            'admin.students.index',
            'admin.students.create',
            'admin.students.show',
            'admin.students.edit',
        );
        $adminMoreNavigationIsActive = request()->routeIs(
            'admin.approval_queue',
            'admin.risk_dashboard',
            'admin.academic_terms.*',
            'admin.partner_companies.*',
            'admin.document_reviews.*',
            'admin.document_requirements.*',
            'admin.students.import*',
            'admin.students.export',
            'admin.report_templates.*',
            'admin.announcements.*',
            'admin.administrators.*',
            'admin.audit.*',
            'admin.backups.*',
            'admin.system-health',
            'admin.mfa.edit',
        );
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
        <header aria-label="Administrator top bar" class="sticky top-0 z-30 border-b border-slate-200/80 bg-white/90 backdrop-blur">
            <div class="mx-auto flex min-h-14 max-w-[90rem] items-center justify-between gap-2 px-3 py-2 sm:min-h-16 sm:gap-4 sm:px-6 sm:py-3 lg:px-8">
                <div class="flex min-w-0 flex-1 items-center gap-2 sm:gap-3">
                    <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-8 w-8 shrink-0 rounded-lg sm:h-9 sm:w-9 lg:hidden" width="36" height="36">
                    <div class="min-w-0">
                        <p class="hidden truncate text-sm font-semibold text-slate-500 sm:block">@yield('eyebrow', 'Administration')</p>
                        <h1 class="truncate text-base font-bold tracking-tight text-slate-950 sm:text-xl">@yield('page-title', 'Overview')</h1>
                    </div>
                </div>
                <div class="flex shrink-0 items-center gap-2">
                    @can('create', App\Models\User::class)
                        <a href="{{ route('admin.students.create') }}" aria-label="Add student" class="btn-primary h-11 min-h-11 w-11 shrink-0 px-0 sm:w-auto sm:px-4">
                            <x-navigation-icon name="add" />
                            <span class="hidden sm:inline">Add student</span>
                        </a>
                    @endcan
                </div>
            </div>
        </header>

        <main id="admin-main-content" class="mx-auto max-w-[90rem] px-4 pt-6 pb-28 sm:px-6 lg:px-8 lg:py-8">
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

    <nav aria-label="Mobile admin navigation" @class([
        'fixed inset-x-0 bottom-0 z-40 grid border-t border-slate-200 bg-white/95 px-1 pt-2 pb-[max(.5rem,env(safe-area-inset-bottom))] shadow-[0_-8px_30px_rgba(15,23,42,0.08)] backdrop-blur lg:hidden print:hidden',
        'grid-cols-4' => auth()->user()?->hasCapability('students.view'),
        'grid-cols-3' => ! auth()->user()?->hasCapability('students.view'),
    ])>
        <a href="{{ route('admin.dashboard') }}" @class(['mobile-nav-link', 'mobile-nav-link-active' => request()->routeIs('admin.dashboard')]) @if (request()->routeIs('admin.dashboard')) aria-current="page" @endif>
            <span class="mobile-nav-icon"><x-navigation-icon name="overview" /></span>
            <span>Overview</span>
        </a>
        <a href="{{ route('admin.alerts.index') }}" @class(['mobile-nav-link', 'mobile-nav-link-active' => request()->routeIs('admin.alerts.*')]) @if (request()->routeIs('admin.alerts.*')) aria-current="page" @endif>
            <span class="mobile-nav-icon">
                <x-navigation-icon name="alerts" />
                @if ($adminUnreadNotificationCount > 0)
                    <span class="absolute -right-1 -top-1 flex min-h-4 min-w-4 items-center justify-center rounded-full bg-amber-500 px-1 text-[0.6rem] font-black leading-none text-slate-950 ring-2 ring-white" aria-hidden="true">{{ min($adminUnreadNotificationCount, 99) }}</span>
                    <span class="sr-only">{{ $adminUnreadNotificationCount }} unread notifications</span>
                @endif
            </span>
            <span>Alerts</span>
        </a>
        @if (auth()->user()?->hasCapability('students.view'))
            <a href="{{ route('admin.students.index') }}" @class(['mobile-nav-link', 'mobile-nav-link-active' => $adminStudentsNavigationIsActive]) @if ($adminStudentsNavigationIsActive) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-navigation-icon name="students" /></span>
                <span>Students</span>
            </a>
        @endif
        <details class="group relative min-w-0">
            <summary @class(['mobile-nav-link cursor-pointer list-none', 'mobile-nav-link-active' => $adminMoreNavigationIsActive]) @if ($adminMoreNavigationIsActive) aria-current="page" @endif>
                <span class="mobile-nav-icon"><x-navigation-icon name="more" /></span>
                <span>More</span>
            </summary>
            <div class="absolute right-1 bottom-full mb-3 max-h-[min(70vh,32rem)] w-[min(22rem,calc(100vw-1rem))] overflow-y-auto overscroll-contain rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl">
                <p class="px-3 pt-2 pb-1 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-400">Operations</p>
                @if (auth()->user()?->hasCapability('approvals.manage'))
                    <a href="{{ route('admin.approval_queue') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.approval_queue')]) @if (request()->routeIs('admin.approval_queue')) aria-current="page" @endif>Approval queue</a>
                @endif
                @if (auth()->user()?->hasCapability('risk.view'))
                    <a href="{{ route('admin.risk_dashboard') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.risk_dashboard')]) @if (request()->routeIs('admin.risk_dashboard')) aria-current="page" @endif>At-risk students</a>
                @endif
                @if (auth()->user()?->hasCapability('academic.manage'))
                    <a href="{{ route('admin.academic_terms.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.academic_terms.*')]) @if (request()->routeIs('admin.academic_terms.*')) aria-current="page" @endif>Academic terms</a>
                @endif
                @if (auth()->user()?->hasCapability('companies.manage'))
                    <a href="{{ route('admin.partner_companies.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.partner_companies.*')]) @if (request()->routeIs('admin.partner_companies.*')) aria-current="page" @endif>Partner companies</a>
                @endif
                @if (auth()->user()?->hasCapability('documents.manage'))
                    <a href="{{ route('admin.document_reviews.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.document_reviews.*')]) @if (request()->routeIs('admin.document_reviews.*')) aria-current="page" @endif>Document reviews</a>
                    <a href="{{ route('admin.document_requirements.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.document_requirements.*')]) @if (request()->routeIs('admin.document_requirements.*')) aria-current="page" @endif>Document requirements</a>
                @endif
                @if (auth()->user()?->hasCapability('students.manage'))
                    <a href="{{ route('admin.students.import') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.students.import*')]) @if (request()->routeIs('admin.students.import*')) aria-current="page" @endif>Student import</a>
                @endif
                @if (auth()->user()?->hasCapability('reports.view'))
                    <a href="{{ route('admin.students.export') }}" class="mobile-menu-link">Student export</a>
                @endif
                @if (auth()->user()?->hasCapability('reports.manage'))
                    <a href="{{ route('admin.report_templates.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.report_templates.*')]) @if (request()->routeIs('admin.report_templates.*')) aria-current="page" @endif>Report templates</a>
                @endif
                @if (auth()->user()?->hasCapability('announcements.manage'))
                    <a href="{{ route('admin.announcements.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.announcements.*')]) @if (request()->routeIs('admin.announcements.*')) aria-current="page" @endif>Announcements</a>
                @endif

                <p class="px-3 pt-4 pb-1 text-[0.65rem] font-bold uppercase tracking-[0.18em] text-slate-400">Governance</p>
                @if (auth()->user()?->hasCapability('administrators.manage'))
                    <a href="{{ route('admin.administrators.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.administrators.*')]) @if (request()->routeIs('admin.administrators.*')) aria-current="page" @endif>Administrators</a>
                @endif
                @if (auth()->user()?->hasCapability('audit.view'))
                    <a href="{{ route('admin.audit.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.audit.*')]) @if (request()->routeIs('admin.audit.*')) aria-current="page" @endif>Audit trail</a>
                @endif
                @if (auth()->user()?->hasCapability('backups.manage'))
                    <a href="{{ route('admin.backups.index') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.backups.*')]) @if (request()->routeIs('admin.backups.*')) aria-current="page" @endif>Database backups</a>
                @endif
                @if (auth()->user()?->hasCapability('system-health.view'))
                    <a href="{{ route('admin.system-health') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.system-health')]) @if (request()->routeIs('admin.system-health')) aria-current="page" @endif>System health</a>
                @endif
                <a href="{{ route('admin.mfa.edit') }}" @class(['mobile-menu-link', 'mobile-menu-link-active' => request()->routeIs('admin.mfa.edit')]) @if (request()->routeIs('admin.mfa.edit')) aria-current="page" @endif>Sign-in security</a>

                <fieldset class="mx-2 mt-3 rounded-xl border border-slate-200 bg-slate-50 p-3">
                    <legend class="px-1 text-[0.65rem] font-bold uppercase tracking-wider text-slate-500">Accessibility</legend>
                    <label class="mt-1 flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" data-accessibility-large-text class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                        Larger text
                    </label>
                    <label class="mt-3 flex cursor-pointer items-center gap-2 text-sm font-semibold text-slate-700">
                        <input type="checkbox" data-accessibility-high-contrast class="h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500">
                        Higher contrast
                    </label>
                </fieldset>
                <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-slate-100 pt-2">
                    @csrf
                    <button type="submit" class="mobile-menu-link w-full text-left text-rose-700 hover:bg-rose-50">Log out</button>
                </form>
            </div>
        </details>
    </nav>

    @stack('scripts')
</body>
</html>
