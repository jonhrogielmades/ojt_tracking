@props(['compact' => false])

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-3']) }}>
    <img src="{{ asset('icons/icon-192.png') }}" alt="" class="h-10 w-10 rounded-xl shadow-sm" width="40" height="40">
    @unless ($compact)
        <span class="leading-tight">
            <span class="block text-base font-bold tracking-tight text-slate-950">OJT Tracker</span>
            <span class="block text-[0.68rem] font-semibold uppercase tracking-[0.2em] text-teal-600">Student workspace</span>
        </span>
    @endunless
</span>
