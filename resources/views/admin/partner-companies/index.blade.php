@extends('layouts.admin')

@section('title', 'Partner companies')
@section('eyebrow', 'Placement directory')
@section('page-title', 'Partner companies')

@section('content')
    @php $company = $editingCompany; @endphp
    <div class="grid gap-6 xl:grid-cols-[0.8fr_1.2fr]">
        <section class="card card-body self-start">
            <h2 class="section-title">{{ $company ? 'Edit partner company' : 'Add partner company' }}</h2>
            <p class="section-copy">Students can link their placement profile to active directory entries.</p>
            <form method="POST" action="{{ $company ? route('admin.partner_companies.update', $company) : route('admin.partner_companies.store') }}" class="mt-6 space-y-4">
                @csrf @if ($company) @method('PATCH') @endif
                <div><label for="name" class="label">Company name</label><input id="name" name="name" required class="input" value="{{ old('name', $company?->name) }}"></div>
                <div><label for="address" class="label">Address</label><textarea id="address" name="address" rows="2" class="input">{{ old('address', $company?->address) }}</textarea></div>
                <div class="grid gap-4 sm:grid-cols-2"><div><label for="contact_name" class="label">Contact person</label><input id="contact_name" name="contact_name" class="input" value="{{ old('contact_name', $company?->contact_name) }}"></div><div><label for="contact_phone" class="label">Phone</label><input id="contact_phone" name="contact_phone" class="input" value="{{ old('contact_phone', $company?->contact_phone) }}"></div></div>
                <div><label for="contact_email" class="label">Contact email</label><input id="contact_email" name="contact_email" type="email" class="input" value="{{ old('contact_email', $company?->contact_email) }}"></div>
                <div><label for="departments_text" class="label">Departments <span class="font-normal text-slate-400">(one per line)</span></label><textarea id="departments_text" name="departments_text" rows="3" class="input">{{ old('departments_text', implode("\n", $company?->departments ?? [])) }}</textarea></div>
                <div><label for="notes" class="label">Internal notes</label><textarea id="notes" name="notes" rows="3" class="input">{{ old('notes', $company?->notes) }}</textarea></div>
                <label class="flex items-center gap-3"><input type="hidden" name="is_active" value="0"><input type="checkbox" name="is_active" value="1" class="rounded border-slate-300 text-teal-700" @checked((bool) old('is_active', $company?->is_active ?? true))><span class="text-sm font-bold text-slate-700">Available for student placement</span></label>
                <div class="flex gap-2"><button type="submit" class="btn-primary flex-1">{{ $company ? 'Save company' : 'Add company' }}</button>@if ($company)<a href="{{ route('admin.partner_companies.index') }}" class="btn-secondary">Cancel</a>@endif</div>
            </form>
        </section>
        <section class="card overflow-hidden">
            <div class="border-b border-slate-200 p-5 sm:px-6"><form method="GET" class="flex gap-2"><label for="q" class="sr-only">Search companies</label><input id="q" name="q" class="input" value="{{ $filters['q'] ?? '' }}" placeholder="Search name or address"><button class="btn-secondary" type="submit">Search</button></form></div>
            <div class="divide-y divide-slate-100">
                @forelse ($companies as $listedCompany)
                    <article class="flex flex-col gap-4 p-5 sm:flex-row sm:items-start sm:justify-between sm:p-6"><div><div class="flex flex-wrap items-center gap-2"><p class="font-bold text-slate-950">{{ $listedCompany->name }}</p><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $listedCompany->is_active, 'bg-slate-200 text-slate-700' => ! $listedCompany->is_active])>{{ $listedCompany->is_active ? 'Active' : 'Inactive' }}</span></div><p class="mt-1 text-sm text-slate-600">{{ $listedCompany->address ?: 'No address recorded' }}</p><p class="mt-2 text-xs text-slate-500">{{ $listedCompany->student_profiles_count }} linked student(s) · {{ $listedCompany->contact_email ?: 'No contact email' }}</p></div><div class="flex gap-2"><a href="{{ route('admin.partner_companies.index', ['edit' => $listedCompany, 'q' => $filters['q'] ?? null]) }}" class="btn-secondary">Edit</a><form method="POST" action="{{ route('admin.partner_companies.destroy', $listedCompany) }}" data-confirm="Remove this company from the directory? Existing profiles keep their company snapshot.">@csrf @method('DELETE')<button type="submit" class="btn-danger">Delete</button></form></div></article>
                @empty
                    <div class="p-6"><x-empty-state title="No partner companies" message="Add an approved company to begin the placement directory." /></div>
                @endforelse
            </div>
            @if ($companies->hasPages())<div class="border-t border-slate-200 px-5 py-4">{{ $companies->links() }}</div>@endif
        </section>
    </div>
@endsection
