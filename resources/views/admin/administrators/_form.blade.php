@php
    $isEditing = $administrator !== null;
    $isActive = (bool) old('is_active', $administrator?->is_active ?? true);
@endphp

<form method="POST" action="{{ $isEditing ? route('admin.administrators.update', $administrator) : route('admin.administrators.store') }}" class="space-y-6">
    @csrf
    @if ($isEditing)
        @method('PUT')
    @endif

    <section class="card card-body">
        <p class="eyebrow">Administrator identity</p>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div>
                <label for="name" class="label">Full name</label>
                <input id="name" name="name" value="{{ old('name', $administrator?->name) }}" required autofocus autocomplete="name" @class(['input', 'input-error' => $errors->has('name')])>
                <x-field-error name="name" />
            </div>
            <div>
                <label for="email" class="label">Email address</label>
                <input id="email" name="email" type="email" value="{{ old('email', $administrator?->email) }}" required autocomplete="email" @class(['input', 'input-error' => $errors->has('email')])>
                <x-field-error name="email" />
            </div>
            <div>
                <label for="role" class="label">Role</label>
                <select id="role" name="role" required @class(['input', 'input-error' => $errors->has('role')])>
                    @foreach ($roles as $role)
                        <option value="{{ $role->value }}" @selected(old('role', $administrator?->role?->value ?? App\Enums\UserRole::OjtCoordinator->value) === $role->value)>{{ $role->label() }}</option>
                    @endforeach
                </select>
                <x-field-error name="role" />
            </div>
            <div>
                <label for="password" class="label">{{ $isEditing ? 'New password (optional)' : 'Initial password' }}</label>
                <input id="password" name="password" type="password" @required(! $isEditing) autocomplete="new-password" @class(['input', 'input-error' => $errors->has('password')])>
                <x-field-error name="password" />
            </div>
            <div>
                <label for="password_confirmation" class="label">Confirm {{ $isEditing ? 'new ' : '' }}password</label>
                <input id="password_confirmation" name="password_confirmation" type="password" @required(! $isEditing) autocomplete="new-password" class="input">
            </div>
            <div class="sm:col-span-2">
                <input type="hidden" name="is_active" value="0">
                <label class="flex cursor-pointer items-start gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-4">
                    <input type="checkbox" name="is_active" value="1" class="mt-0.5 h-4 w-4 rounded border-slate-300 text-teal-700 focus:ring-teal-500" @checked($isActive)>
                    <span><span class="block text-sm font-bold text-slate-900">Active administrator account</span><span class="mt-1 block text-xs leading-5 text-slate-500">Inactive administrators cannot sign in.</span></span>
                </label>
            </div>
        </div>
    </section>

    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
        <a href="{{ route('admin.administrators.index') }}" class="btn-secondary">Cancel</a>
        <button type="submit" class="btn-primary">{{ $isEditing ? 'Save administrator' : 'Create administrator' }}</button>
    </div>
</form>
