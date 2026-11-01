@extends('layouts.app')

@section('title', 'Install & offline help')
@section('eyebrow', 'Progressive web app')
@section('page-title', 'Install & offline help')

@section('content')
    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="card card-body bg-slate-950 text-white">
            <img src="{{ asset('icons/icon-192.png') }}" alt="OJT Tracker app icon" class="h-20 w-20 rounded-3xl shadow-2xl" width="80" height="80">
            <p class="mt-6 text-xs font-bold uppercase tracking-[0.2em] text-teal-300">Installable app</p><h2 class="mt-2 text-3xl font-bold tracking-tight">Keep OJT Tracker one tap away.</h2><p class="mt-4 leading-7 text-slate-300">Install it on a supported phone, tablet, or desktop for a standalone app window and quick access from your home screen.</p>
            <button type="button" data-install-button hidden class="mt-7 w-full rounded-xl bg-teal-500 px-4 py-3 text-sm font-bold text-slate-950 hover:bg-teal-400">Install OJT Tracker</button>
            <p class="mt-4 text-xs leading-5 text-slate-400">If the button does not appear, follow the browser-specific instructions beside this card.</p>
        </section>

        <section class="space-y-4">
            <details open class="card group p-5"><summary class="cursor-pointer list-none font-bold text-slate-950">Chrome or Edge on desktop <span class="float-right text-slate-400 group-open:rotate-180">⌄</span></summary><ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600"><li>Open OJT Tracker in Chrome or Microsoft Edge.</li><li>Select the install icon in the address bar, or open the browser menu and choose <strong>Install OJT Tracker</strong> / <strong>Apps → Install this site as an app</strong>.</li><li>Confirm the installation.</li></ol></details>
            <details class="card group p-5"><summary class="cursor-pointer list-none font-bold text-slate-950">Android (Chrome) <span class="float-right text-slate-400 group-open:rotate-180">⌄</span></summary><ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600"><li>Open this site in Chrome.</li><li>Tap the three-dot menu.</li><li>Choose <strong>Install app</strong> or <strong>Add to Home screen</strong>, then confirm.</li></ol></details>
            <details class="card group p-5"><summary class="cursor-pointer list-none font-bold text-slate-950">iPhone or iPad (Safari) <span class="float-right text-slate-400 group-open:rotate-180">⌄</span></summary><ol class="mt-4 list-decimal space-y-2 pl-5 text-sm leading-6 text-slate-600"><li>Open this site in Safari.</li><li>Tap the Share button.</li><li>Choose <strong>Add to Home Screen</strong>, then tap <strong>Add</strong>.</li></ol></details>
        </section>
    </div>

    <section class="mt-6 card card-body">
        <p class="eyebrow">Offline behavior</p><h2 class="section-title mt-2">What works without a connection</h2>
        <div class="mt-5 grid gap-4 md:grid-cols-3">
            <article class="rounded-2xl bg-teal-50 p-5"><h3 class="font-bold text-teal-950">Draft new entries</h3><p class="mt-2 text-sm leading-6 text-teal-800">After one online sign-in on this device, an offline launch offers local DTR and text-only activity drafts. Attachments are never stored offline.</p></article>
            <article class="rounded-2xl bg-amber-50 p-5"><h3 class="font-bold text-amber-950">Review before sync</h3><p class="mt-2 text-sm leading-6 text-amber-800">Use the Pending button to retry or remove drafts. Conflicts stay pending and explain what needs attention.</p></article>
            <article class="rounded-2xl bg-sky-50 p-5"><h3 class="font-bold text-sky-950">Private by design</h3><p class="mt-2 text-sm leading-6 text-sky-800">Authenticated pages, saved records, passwords, and private files are never cached by the service worker.</p></article>
        </div>
        <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 px-4 py-3 text-sm leading-6 text-slate-600"><strong class="text-slate-900">Important:</strong> Official DTR hours exist only after the server validates and syncs the draft. Normal logout warns about pending data and can sync or delete it before the session ends.</div>
    </section>
@endsection
