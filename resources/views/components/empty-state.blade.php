@props(['title', 'message', 'action' => null, 'actionLabel' => null])

<div {{ $attributes->merge(['class' => 'rounded-2xl border border-dashed border-slate-300 bg-slate-50/70 px-6 py-10 text-center']) }}>
    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-white text-teal-600 shadow-sm ring-1 ring-slate-200">
        <svg aria-hidden="true" viewBox="0 0 24 24" fill="none" class="h-6 w-6" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h3m-5 5h10a2 2 0 0 0 2-2V7.8a2 2 0 0 0-.59-1.42l-3.8-3.79A2 2 0 0 0 13.2 2H7a2 2 0 0 0-2 2v15a2 2 0 0 0 2 2Z" /></svg>
    </div>
    <h3 class="mt-4 text-base font-bold text-slate-900">{{ $title }}</h3>
    <p class="mx-auto mt-1 max-w-md text-sm leading-6 text-slate-500">{{ $message }}</p>
    @if ($action && $actionLabel)
        <a href="{{ $action }}" class="btn-primary mt-5">{{ $actionLabel }}</a>
    @endif
</div>
