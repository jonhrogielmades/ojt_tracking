@extends('layouts.admin')

@section('title', 'Administrators')
@section('eyebrow', 'Access governance')
@section('page-title', 'Administrators')

@section('content')
    <section class="card card-body">
        <form method="GET" action="{{ route('admin.administrators.index') }}" class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_14rem_12rem_auto] lg:items-end">
            <div>
                <label for="q" class="label">Search administrators</label>
                <input id="q" name="q" value="{{ $filters['q'] ?? '' }}" class="input" placeholder="Name or email">
            </div>
            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" class="input">
                    <option value="">All roles</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(($filters['role'] ?? '') === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="label">Status</label>
                <select id="status" name="status" class="input"><option value="">All statuses</option><option value="active" @selected(($filters['status'] ?? '') === 'active')>Active</option><option value="inactive" @selected(($filters['status'] ?? '') === 'inactive')>Inactive</option></select>
            </div>
            <button type="submit" class="btn-primary">Apply filters</button>
        </form>
    </section>

    <section class="mt-6 card overflow-hidden">
        <div class="flex flex-col gap-3 border-b border-slate-200 px-5 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6">
            <div><h2 class="section-title">Administrator accounts</h2><p class="section-copy">{{ number_format($administrators->total()) }} accounts found</p></div>
            <a href="{{ route('admin.administrators.create') }}" class="btn-primary">Add administrator</a>
        </div>
        @if ($administrators->isEmpty())
            <div class="p-6"><x-empty-state title="No administrators found" message="Try another filter or create an administrator account." :action="route('admin.administrators.create')" action-label="Add administrator" /></div>
        @else
            <div class="overflow-x-auto">
                <table class="data-table">
                    <thead><tr><th>Administrator</th><th>Role</th><th>Verification</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr></thead>
                    <tbody>
                        @foreach ($administrators as $administrator)
                            <tr>
                                <td><p class="font-bold text-slate-950">{{ $administrator->name }}</p><p class="mt-0.5 text-xs text-slate-500">{{ $administrator->email }}</p></td>
                                <td><span class="status-pill bg-sky-100 text-sky-800">{{ $administrator->role->label() }}</span></td>
                                <td>{{ $administrator->mfa_enabled ? 'Email code enabled' : 'Password only' }}</td>
                                <td><span @class(['status-pill', 'bg-emerald-100 text-emerald-800' => $administrator->is_active, 'bg-amber-100 text-amber-800' => ! $administrator->is_active])>{{ $administrator->is_active ? 'Active' : 'Inactive' }}</span></td>
                                <td class="text-right"><a href="{{ route('admin.administrators.edit', $administrator) }}" class="text-sm font-bold text-teal-700 hover:text-teal-800">Edit</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-200 px-5 py-4 sm:px-6">{{ $administrators->links() }}</div>
        @endif
    </section>
@endsection
