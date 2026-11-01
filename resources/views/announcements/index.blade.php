@extends('layouts.app')

@section('title', 'Announcements')
@section('eyebrow', 'Coordinator updates')
@section('page-title', 'Announcements')

@section('content')
    <section class="space-y-4">
        @forelse ($announcements as $announcement)
            <article class="card card-body"><div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between"><div><h2 class="section-title">{{ $announcement->title }}</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-slate-700">{{ $announcement->body }}</p></div><time class="whitespace-nowrap text-xs font-semibold text-slate-500" datetime="{{ $announcement->published_at?->toIso8601String() }}">{{ $announcement->published_at?->format('M j, Y') }}</time></div>@if ($announcement->expires_at)<p class="mt-4 border-t border-slate-200 pt-3 text-xs text-slate-500">Available until {{ $announcement->expires_at->format('M j, Y g:i A') }}</p>@endif</article>
        @empty
            <div class="card card-body"><x-empty-state title="No announcements" message="Program, section, and campus-wide updates will appear here." /></div>
        @endforelse
        @if ($announcements->hasPages())<div class="card px-5 py-4">{{ $announcements->links() }}</div>@endif
    </section>
@endsection
