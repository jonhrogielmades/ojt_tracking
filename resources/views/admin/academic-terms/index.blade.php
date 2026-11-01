@extends('layouts.admin')

@section('title', 'Academic terms')
@section('eyebrow', 'Academic configuration')
@section('page-title', 'Terms, programs & holidays')

@section('content')
    @php
        $term = $editingTerm;
        $programsText = old('programs_text', implode("\n", $term?->programs ?? []));
        $sectionsText = old('sections_text', implode("\n", $term?->sections ?? []));
        $holidaysText = old('holidays_text', collect($term?->holidays ?? [])->map(fn ($holiday) => ($holiday['date'] ?? '').'|'.($holiday['name'] ?? 'Holiday'))->implode("\n"));
    @endphp
    <div class="grid gap-6 xl:grid-cols-[0.85fr_1.15fr]">
        <section class="card card-body self-start">
            <p class="eyebrow">{{ $term ? 'Edit configuration' : 'New configuration' }}</p>
            <h2 class="section-title mt-2">{{ $term?->name ?? 'Add an academic term' }}</h2>
            <form method="POST" action="{{ $term ? route('admin.academic_terms.update', $term) : route('admin.academic_terms.store') }}" class="mt-6 space-y-5">
                @csrf @if ($term) @method('PATCH') @endif
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="name" class="label">Term name</label><input id="name" name="name" required class="input" value="{{ old('name', $term?->name) }}" placeholder="First Semester 2026–2027"></div><div><label for="code" class="label">Code</label><input id="code" name="code" required class="input" value="{{ old('code', $term?->code) }}" placeholder="2026-1"></div></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="starts_on" class="label">Start date</label><input id="starts_on" name="starts_on" type="date" required class="input" value="{{ old('starts_on', $term?->starts_on?->format('Y-m-d')) }}"></div><div><label for="ends_on" class="label">End date</label><input id="ends_on" name="ends_on" type="date" required class="input" value="{{ old('ends_on', $term?->ends_on?->format('Y-m-d')) }}"></div></div>
                <div><label for="required_hours" class="label">Default required hours</label><input id="required_hours" name="required_hours" type="number" min="1" max="10000" step="0.25" required class="input" value="{{ old('required_hours', $term ? number_format($term->required_minutes / 60, 2, '.', '') : 486) }}"></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="programs_text" class="label">Programs <span class="font-normal text-slate-400">(one per line)</span></label><textarea id="programs_text" name="programs_text" rows="5" class="input" placeholder="BSIT&#10;BSCS">{{ $programsText }}</textarea></div><div><label for="sections_text" class="label">Sections <span class="font-normal text-slate-400">(one per line)</span></label><textarea id="sections_text" name="sections_text" rows="5" class="input" placeholder="4A&#10;4B">{{ $sectionsText }}</textarea></div></div>
                <div><label for="holidays_text" class="label">Holidays <span class="font-normal text-slate-400">(YYYY-MM-DD|Name, one per line)</span></label><textarea id="holidays_text" name="holidays_text" rows="5" class="input" placeholder="2026-11-30|Bonifacio Day">{{ $holidaysText }}</textarea></div>
                <label class="flex items-center gap-3 rounded-xl border border-slate-200 p-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700" @checked((bool) old('is_active', $term?->is_active ?? false))><span><span class="block text-sm font-bold text-slate-800">Active term</span><span class="block text-xs text-slate-500">Activating this term automatically deactivates the previous one.</span></span></label>
                <div class="flex gap-2"><button class="btn-primary flex-1" type="submit">{{ $term ? 'Save changes' : 'Create term' }}</button>@if ($term)<a href="{{ route('admin.academic_terms.index') }}" class="btn-secondary">Cancel</a>@endif</div>
            </form>
        </section>

        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Configured terms</h2><p class="section-copy">The active term supplies shared dates, program lists, required hours, and holidays.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($terms as $configuredTerm)
                    <article class="p-5 sm:p-6"><div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $configuredTerm->name }}</p>@if ($configuredTerm->is_active)<span class="status-pill bg-emerald-100 text-emerald-800">Active</span>@endif</div><p class="mt-1 text-sm text-slate-600">{{ $configuredTerm->code }} · {{ $configuredTerm->starts_on->format('M j, Y') }} – {{ $configuredTerm->ends_on->format('M j, Y') }}</p><p class="mt-2 text-xs text-slate-500">{{ number_format($configuredTerm->required_minutes / 60, 2) }} required hours · {{ count($configuredTerm->programs ?? []) }} programs · {{ count($configuredTerm->holidays ?? []) }} holidays</p></div><div class="flex gap-2"><a href="{{ route('admin.academic_terms.index', ['edit' => $configuredTerm]) }}" class="btn-secondary">Edit</a>@unless ($configuredTerm->is_active)<form method="POST" action="{{ route('admin.academic_terms.destroy', $configuredTerm) }}" data-confirm="Delete this academic term?">@csrf @method('DELETE')<button class="btn-danger" type="submit">Delete</button></form>@endunless</div></div></article>
                @empty
                    <div class="p-6"><x-empty-state title="No academic terms" message="Create the first term to centralize dates, requirements, and holidays." /></div>
                @endforelse
            </div>
            @if ($terms->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $terms->links() }}</div>@endif
        </section>
    </div>
@endsection
