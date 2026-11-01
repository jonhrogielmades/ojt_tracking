@extends('layouts.admin')

@section('title', 'Document requirements')
@section('eyebrow', 'Compliance checklist')
@section('page-title', 'Document requirements')

@section('content')
    @php $requirement = $editingRequirement; @endphp
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="card card-body self-start">
            <h2 class="section-title">{{ $requirement ? 'Edit requirement' : 'Add requirement' }}</h2>
            <form method="POST" action="{{ $requirement ? route('admin.document_requirements.update', $requirement) : route('admin.document_requirements.store') }}" class="mt-6 space-y-4">
                @csrf @if ($requirement) @method('PATCH') @endif
                <div><label for="name" class="label">Requirement name</label><input id="name" name="name" required class="input" value="{{ old('name', $requirement?->name) }}"></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="category" class="label">Category</label><input id="category" name="category" class="input" value="{{ old('category', $requirement?->category) }}"></div><div><label for="due_date" class="label">Due date</label><input id="due_date" name="due_date" type="date" class="input" value="{{ old('due_date', $requirement?->due_date?->format('Y-m-d')) }}"></div></div>
                <div><label for="program" class="label">Target program</label><input id="program" name="program" list="program-options" class="input" value="{{ old('program', $requirement?->program) }}" placeholder="Leave blank for all programs"><datalist id="program-options">@foreach ($programs as $program)<option value="{{ $program }}">@endforeach</datalist></div>
                <div><label for="description" class="label">Instructions</label><textarea id="description" name="description" rows="4" class="input">{{ old('description', $requirement?->description) }}</textarea></div>
                <div class="grid gap-3 sm:grid-cols-2"><label class="flex items-center gap-3"><input type="hidden" name="is_required" value="0"><input type="checkbox" name="is_required" value="1" class="rounded border-slate-300 text-teal-700" @checked((bool) old('is_required', $requirement?->is_required ?? true))><span class="text-sm font-bold text-slate-700">Required</span></label><label class="flex items-center gap-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700" @checked((bool) old('is_active', $requirement?->is_active ?? true))><span class="text-sm font-bold text-slate-700">Active</span></label></div>
                <div class="flex gap-2"><button type="submit" class="btn-primary flex-1">{{ $requirement ? 'Save requirement' : 'Add requirement' }}</button>@if ($requirement)<a href="{{ route('admin.document_requirements.index') }}" class="btn-secondary">Cancel</a>@endif</div>
            </form>
        </section>
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 px-5 py-4 sm:px-6"><h2 class="section-title">Checklist items</h2><p class="section-copy">Students see active items for all programs or their exact program.</p></div>
            <div class="divide-y divide-slate-100">
                @forelse ($requirements as $listedRequirement)
                    <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $listedRequirement->name }}</p><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $listedRequirement->is_active, 'bg-slate-200 text-slate-700' => ! $listedRequirement->is_active])>{{ $listedRequirement->is_active ? 'Active' : 'Inactive' }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $listedRequirement->program ?: 'All programs' }} · {{ $listedRequirement->due_date?->format('M j, Y') ?? 'No due date' }}</p><p class="mt-2 text-xs text-slate-500">{{ $listedRequirement->student_documents_count }} linked upload(s) · {{ $listedRequirement->is_required ? 'Required' : 'Optional' }}</p></div><div class="flex gap-2"><a href="{{ route('admin.document_requirements.index', ['edit' => $listedRequirement]) }}" class="btn-secondary">Edit</a><form method="POST" action="{{ route('admin.document_requirements.destroy', $listedRequirement) }}" data-confirm="Delete this requirement? Linked uploads will remain available.">@csrf @method('DELETE')<button type="submit" class="btn-danger">Delete</button></form></div></article>
                @empty
                    <div class="p-6"><x-empty-state title="No document requirements" message="Create a checklist item and optionally target a program and due date." /></div>
                @endforelse
            </div>
            @if ($requirements->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $requirements->links() }}</div>@endif
        </section>
    </div>
@endsection
