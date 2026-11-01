@extends('layouts.admin')

@section('title', 'Report templates')
@section('eyebrow', 'Institutional reports')
@section('page-title', 'Report templates')

@section('content')
    @php $template = $editingTemplate; @endphp
    <div class="grid gap-6 xl:grid-cols-[0.9fr_1.1fr]">
        <section class="card card-body self-start">
            <h2 class="section-title">{{ $template ? 'Edit report template' : 'Add report template' }}</h2>
            <form method="POST" action="{{ $template ? route('admin.report_templates.update', $template) : route('admin.report_templates.store') }}" class="mt-6 space-y-4">
                @csrf @if ($template) @method('PATCH') @endif
                <div><label for="name" class="label">Template name</label><input id="name" name="name" required class="input" value="{{ old('name', $template?->name) }}" placeholder="Default institutional template"></div>
                <div><label for="institution_name" class="label">Institution name</label><input id="institution_name" name="institution_name" required class="input" value="{{ old('institution_name', $template?->institution_name) }}"></div>
                <div><label for="office_name" class="label">Office / department</label><input id="office_name" name="office_name" class="input" value="{{ old('office_name', $template?->office_name) }}"></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="monthly_title" class="label">Monthly report title</label><input id="monthly_title" name="monthly_title" required class="input" value="{{ old('monthly_title', $template?->monthly_title ?? 'OJT Monthly DTR Report') }}"></div><div><label for="weekly_title" class="label">Weekly report title</label><input id="weekly_title" name="weekly_title" required class="input" value="{{ old('weekly_title', $template?->weekly_title ?? 'OJT Weekly Activity Report') }}"></div></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="prepared_by_label" class="label">Prepared-by label</label><input id="prepared_by_label" name="prepared_by_label" required class="input" value="{{ old('prepared_by_label', $template?->prepared_by_label ?? 'Prepared by') }}"></div><div><label for="approved_by_label" class="label">Approval label</label><input id="approved_by_label" name="approved_by_label" required class="input" value="{{ old('approved_by_label', $template?->approved_by_label ?? 'Approved by') }}"></div></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="signatory_name" class="label">Signatory name</label><input id="signatory_name" name="signatory_name" class="input" value="{{ old('signatory_name', $template?->signatory_name) }}"></div><div><label for="signatory_title" class="label">Signatory title</label><input id="signatory_title" name="signatory_title" class="input" value="{{ old('signatory_title', $template?->signatory_title) }}"></div></div>
                <label class="flex items-center gap-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700" @checked((bool) old('is_active', $template?->is_active ?? false))><span class="text-sm font-bold text-slate-700">Use for student reports</span></label>
                <div class="flex gap-2"><button type="submit" class="btn-primary flex-1">{{ $template ? 'Save template' : 'Create template' }}</button>@if ($template)<a href="{{ route('admin.report_templates.index') }}" class="btn-secondary">Cancel</a>@endif</div>
            </form>
        </section>
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Available templates</h2></div>
            <div class="divide-y divide-slate-100">
                @forelse ($templates as $listedTemplate)
                    <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $listedTemplate->name }}</p>@if ($listedTemplate->is_active)<span class="status-pill bg-emerald-100 text-emerald-800">Active</span>@endif</div><p class="mt-1 text-sm text-slate-600">{{ $listedTemplate->institution_name }}</p><p class="mt-2 text-xs text-slate-500">{{ $listedTemplate->monthly_title }} · {{ $listedTemplate->weekly_title }}</p></div><div class="flex gap-2"><a href="{{ route('admin.report_templates.index', ['edit' => $listedTemplate]) }}" class="btn-secondary">Edit</a>@unless ($listedTemplate->is_active)<form method="POST" action="{{ route('admin.report_templates.destroy', $listedTemplate) }}" data-confirm="Delete this report template?">@csrf @method('DELETE')<button type="submit" class="btn-danger">Delete</button></form>@endunless</div></article>
                @empty
                    <div class="p-6"><x-empty-state title="No report templates" message="Create a template to add institutional titles and signatory details to student reports." /></div>
                @endforelse
            </div>
            @if ($templates->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $templates->links() }}</div>@endif
        </section>
    </div>
@endsection
