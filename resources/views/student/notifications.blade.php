@extends('layouts.app')

@section('title', 'Notifications')
@section('eyebrow', 'Inbox')
@section('page-title', 'Notifications')

@section('content')
    <div class="mb-6 flex items-center justify-between gap-4">
        <p class="section-copy mt-0">Review decisions, correction updates, and timely OJT reminders appear here.</p>
        @if ($notifications->contains(fn ($notification) => $notification->unread()))
            <form method="POST" action="{{ route('student.notifications.read-all') }}">@csrf @method('PATCH')<button type="submit" class="btn-secondary">Mark all read</button></form>
        @endif
    </div>

    <section class="card overflow-hidden">
        <div class="divide-y divide-slate-200">
            @forelse ($notifications as $notification)
                @php
                    $actionUrl = $notification->data['action_url'] ?? $notification->data['url'] ?? null;
                @endphp
                <article class="p-5 sm:p-6 {{ $notification->unread() ? 'bg-teal-50/50' : 'bg-white' }}">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div class="min-w-0">
                            <div class="flex items-center gap-2">@if ($notification->unread())<span class="h-2.5 w-2.5 rounded-full bg-teal-600" aria-label="Unread"></span>@endif<h2 class="font-bold text-slate-950">{{ $notification->data['title'] ?? 'OJT update' }}</h2></div>
                            <p class="mt-2 text-sm leading-6 text-slate-600">{{ $notification->data['message'] ?? '' }}</p>
                            <p class="mt-2 text-xs text-slate-400">{{ $notification->created_at->diffForHumans() }}</p>
                        </div>
                        <div class="flex shrink-0 flex-wrap gap-2">
                            @if (filled($actionUrl))<a href="{{ $actionUrl }}" class="btn-secondary">Open</a>@endif
                            @if ($notification->unread())<form method="POST" action="{{ route('student.notifications.read', $notification->id) }}">@csrf @method('PATCH')<button type="submit" class="btn-ghost">Mark read</button></form>@endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="p-6"><x-empty-state title="Your inbox is clear" message="Review decisions and reminders will appear here." /></div>
            @endforelse
        </div>
        @if ($notifications->hasPages())<div class="border-t border-slate-200 p-4">{{ $notifications->links() }}</div>@endif
    </section>
@endsection
