@extends('layouts.admin')

@section('title', 'Notification center')
@section('eyebrow', 'Operations')
@section('page-title', 'Notification center')

@section('content')
    <div class="mb-6 flex flex-col gap-2 sm:flex-row sm:items-end sm:justify-between">
        <div>
            <p class="eyebrow">Actionable updates</p>
            <p class="section-copy">Review the work and system signals available to your administrator role.</p>
        </div>
        @if ($notifications->contains(fn ($notification) => $notification->unread()))
            <form method="POST" action="{{ route('admin.alerts.read-all') }}">
                @csrf
                @method('PATCH')
                <button type="submit" class="btn-secondary">Mark notifications read</button>
            </form>
        @endif
    </div>

    <section aria-label="Alert summary" class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
        @if ($canManageApprovals)
            <article class="stat-card">
                <p class="text-sm font-semibold text-slate-500">Weekly reports</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($submittedWeeklyCount) }}</p>
                <a href="{{ route('admin.approval_queue') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-teal-700 hover:text-teal-800">Open approval queue</a>
            </article>
            <article class="stat-card">
                <p class="text-sm font-semibold text-slate-500">Correction requests</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($pendingCorrectionCount) }}</p>
                <a href="{{ route('admin.approval_queue') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-teal-700 hover:text-teal-800">Review corrections</a>
            </article>
        @endif
        @if ($canManageDocuments)
            <article class="stat-card">
                <p class="text-sm font-semibold text-slate-500">Documents awaiting review</p>
                <p class="mt-3 text-3xl font-bold text-slate-950">{{ number_format($pendingDocumentCount) }}</p>
                <a href="{{ route('admin.document_reviews.index', ['status' => 'pending']) }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-teal-700 hover:text-teal-800">Review documents</a>
            </article>
        @endif
        @if ($canViewSystemHealth)
            <article class="stat-card">
                <p class="text-sm font-semibold text-slate-500">Failed queue jobs</p>
                <p @class(['mt-3 text-3xl font-bold', 'text-slate-950' => ($failedJobCount ?? 0) === 0, 'text-rose-700' => ($failedJobCount ?? 0) > 0])>{{ $failedJobCount === null ? 'N/A' : number_format($failedJobCount) }}</p>
                <a href="{{ route('admin.system-health') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-teal-700 hover:text-teal-800">View system health</a>
            </article>
        @endif
        @if ($canManageBackups && ! $canViewSystemHealth)
            <article class="stat-card">
                <p class="text-sm font-semibold text-slate-500">Latest database backup</p>
                <p class="mt-3 text-xl font-bold text-slate-950">{{ $latestBackup?->created_at?->diffForHumans() ?? 'None recorded' }}</p>
                <a href="{{ route('admin.backups.index') }}" class="mt-3 inline-flex min-h-11 items-center text-sm font-bold text-teal-700 hover:text-teal-800">Manage backups</a>
            </article>
        @endif
    </section>

    @if ($canViewSystemHealth && $latestBackup !== null)
        <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-card sm:flex sm:items-center sm:justify-between sm:gap-4">
            <div>
                <h2 class="font-bold text-slate-950">Latest backup: {{ ucfirst($latestBackup->status) }}</h2>
                <p class="mt-1 text-sm text-slate-500">Created {{ $latestBackup->created_at->diffForHumans() }}{{ $latestBackup->verified_at ? ' · verified '.$latestBackup->verified_at->diffForHumans() : '' }}</p>
            </div>
            @if ($canManageBackups)<a href="{{ route('admin.backups.index') }}" class="btn-secondary mt-4 sm:mt-0">Manage backups</a>@endif
        </section>
    @endif

    @if ($canManageApprovals || $canManageDocuments)
        <section class="mt-6 grid gap-6 xl:grid-cols-2">
            @if ($canManageApprovals)
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Oldest approval work</h2><p class="section-copy">Submitted reports and corrections waiting longest.</p></div>
                    <div class="divide-y divide-slate-100">
                        @foreach ($submittedWeeklyReports as $submission)
                            <article class="p-5"><p class="font-bold text-slate-950">{{ $submission->user?->name }} · {{ $submission->user?->student_id }}</p><p class="mt-1 text-sm text-slate-600">Weekly report for {{ $submission->week_start->format('M j, Y') }}</p></article>
                        @endforeach
                        @foreach ($pendingCorrections as $correction)
                            <article class="p-5"><p class="font-bold text-slate-950">{{ $correction->user?->name }} · {{ $correction->user?->student_id }}</p><p class="mt-1 text-sm text-slate-600">Correction for {{ $correction->dtrEntry?->work_date?->format('M j, Y') }}</p></article>
                        @endforeach
                        @if ($submittedWeeklyReports->isEmpty() && $pendingCorrections->isEmpty())<div class="p-6"><x-empty-state title="Approval queue is clear" message="New weekly reports and correction requests will appear here." /></div>@endif
                    </div>
                </div>
            @endif
            @if ($canManageDocuments)
                <div class="card overflow-hidden">
                    <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Oldest document reviews</h2><p class="section-copy">Private uploads waiting for a decision.</p></div>
                    <div class="divide-y divide-slate-100">
                        @forelse ($pendingDocuments as $document)
                            <article class="p-5"><p class="font-bold text-slate-950">{{ $document->title }}</p><p class="mt-1 text-sm text-slate-600">{{ $document->user?->name }} · {{ $document->user?->student_id }}</p><p class="mt-1 text-xs text-slate-500">{{ $document->requirement?->name ?? 'General document' }}</p></article>
                        @empty
                            <div class="p-6"><x-empty-state title="Document queue is clear" message="New student uploads will appear here." /></div>
                        @endforelse
                    </div>
                </div>
            @endif
        </section>
    @endif

    <section class="card mt-6 overflow-hidden">
        <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Your administrator notifications</h2><p class="section-copy">Messages delivered specifically to your account.</p></div>
        <div class="divide-y divide-slate-100">
            @forelse ($notifications as $notification)
                <article @class(['p-5 sm:p-6', 'bg-teal-50/50' => $notification->unread()])>
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0"><div class="flex items-center gap-2">@if ($notification->unread())<span class="h-2.5 w-2.5 rounded-full bg-teal-600"><span class="sr-only">Unread</span></span>@endif<h3 class="font-bold text-slate-950">{{ $notification->data['title'] ?? 'Administrator update' }}</h3></div><p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p><p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p></div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if (filled($notification->data['action_url'] ?? $notification->data['url'] ?? null))<a href="{{ $notification->data['action_url'] ?? $notification->data['url'] }}" class="btn-secondary">Open</a>@endif
                            @if ($notification->unread())<form method="POST" action="{{ route('admin.alerts.read', $notification->id) }}">@csrf @method('PATCH')<button type="submit" class="btn-ghost">Mark read</button></form>@endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="p-6"><x-empty-state title="No administrator notifications" message="Account-specific messages will appear here." /></div>
            @endforelse
        </div>
        @if ($notifications->hasPages())<div class="border-t border-slate-200 p-4">{{ $notifications->links() }}</div>@endif
    </section>
@endsection
