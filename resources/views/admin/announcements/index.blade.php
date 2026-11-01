@extends('layouts.admin')

@section('title', 'Announcements')
@section('eyebrow', 'Student communications')
@section('page-title', 'Announcements & broadcasts')

@section('content')
    @php $announcement = $editingAnnouncement; @endphp
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="card card-body self-start">
            <h2 class="section-title">{{ $announcement ? 'Edit draft' : 'Create announcement' }}</h2>
            <p class="section-copy">Save a draft first, then publish it to queue database notifications in bounded chunks.</p>
            <form method="POST" action="{{ $announcement ? route('admin.announcements.update', $announcement) : route('admin.announcements.store') }}" class="mt-6 space-y-4">
                @csrf @if ($announcement) @method('PATCH') @endif
                <div><label for="title" class="label">Title</label><input id="title" name="title" required class="input" value="{{ old('title', $announcement?->title) }}"></div>
                <div><label for="body" class="label">Message</label><textarea id="body" name="body" rows="6" required class="input">{{ old('body', $announcement?->body) }}</textarea></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="audience" class="label">Audience</label><select id="audience" name="audience" class="input"><option value="all" @selected(old('audience', $announcement?->audience ?? 'all') === 'all')>All students</option><option value="program" @selected(old('audience', $announcement?->audience) === 'program')>One program</option><option value="section" @selected(old('audience', $announcement?->audience) === 'section')>One section</option></select></div><div><label for="audience_value" class="label">Program or section value</label><input id="audience_value" name="audience_value" list="audience-values" class="input" value="{{ old('audience_value', $announcement?->audience_value) }}" placeholder="Leave blank for all"><datalist id="audience-values">@foreach ($programs as $program)<option value="{{ $program }}">@endforeach @foreach ($sections as $section)<option value="{{ $section }}">@endforeach</datalist></div></div>
                <div><label for="expires_at" class="label">Expires at <span class="font-normal text-slate-400">(optional)</span></label><input id="expires_at" name="expires_at" type="datetime-local" class="input" value="{{ old('expires_at', $announcement?->expires_at?->format('Y-m-d\TH:i')) }}"></div>
                <div class="flex gap-2"><button type="submit" class="btn-primary flex-1">{{ $announcement ? 'Save draft' : 'Create draft' }}</button>@if ($announcement)<a href="{{ route('admin.announcements.index') }}" class="btn-secondary">Cancel</a>@endif</div>
            </form>
        </section>
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Broadcast history</h2><p class="section-copy">Publishing is explicit so drafts cannot be sent accidentally.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($announcements as $listedAnnouncement)
                    @php
                        $isExpiredUndelivered = $listedAnnouncement->published_at
                            && ! $listedAnnouncement->sent_at
                            && $listedAnnouncement->expires_at?->isPast();
                    @endphp
                    <article class="p-5 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <p class="font-bold text-slate-950">{{ $listedAnnouncement->title }}</p>
                                    <span @class([
                                        'status-pill',
                                        'bg-emerald-100 text-emerald-800' => $listedAnnouncement->sent_at,
                                        'bg-red-100 text-red-800' => $isExpiredUndelivered,
                                        'bg-amber-100 text-amber-800' => $listedAnnouncement->published_at && ! $listedAnnouncement->sent_at && ! $isExpiredUndelivered,
                                        'bg-slate-200 text-slate-700' => ! $listedAnnouncement->published_at,
                                    ])>
                                        {{ $listedAnnouncement->sent_at ? 'Dispatched' : ($isExpiredUndelivered ? 'Expired — undelivered' : ($listedAnnouncement->published_at ? 'Delivery pending' : 'Draft')) }}
                                    </span>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-600">{{ $listedAnnouncement->body }}</p>
                                <p class="mt-2 text-xs text-slate-500">Audience: {{ str($listedAnnouncement->audience)->title() }}{{ $listedAnnouncement->audience_value ? ' · '.$listedAnnouncement->audience_value : '' }} · Created by {{ $listedAnnouncement->creator?->name ?? 'Deleted administrator' }}</p>
                            </div>

                            @if (! $listedAnnouncement->published_at)
                                <div class="flex shrink-0 flex-wrap gap-2">
                                    <a href="{{ route('admin.announcements.index', ['edit' => $listedAnnouncement]) }}" class="btn-secondary">Edit</a>
                                    <form method="POST" action="{{ route('admin.announcements.publish', $listedAnnouncement) }}" data-confirm="Publish this announcement to its selected audience?">
                                        @csrf
                                        <button type="submit" class="btn-primary">Publish</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.announcements.destroy', $listedAnnouncement) }}" data-confirm="Delete this draft announcement?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn-danger">Delete</button>
                                    </form>
                                </div>
                            @elseif (! $listedAnnouncement->sent_at && ! $isExpiredUndelivered)
                                <form method="POST" action="{{ route('admin.announcements.retry', $listedAnnouncement) }}" data-confirm="Queue delivery again? Students already reached will not receive a duplicate.">
                                    @csrf
                                    <button type="submit" class="btn-secondary">Retry delivery</button>
                                </form>
                            @endif
                        </div>
                    </article>
                @empty
                    <div class="p-6"><x-empty-state title="No announcements" message="Create a draft to communicate with all students or a targeted program or section." /></div>
                @endforelse
            </div>
            @if ($announcements->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $announcements->links() }}</div>@endif
        </section>
    </div>
@endsection
