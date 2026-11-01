<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdministratorRequest;
use App\Http\Requests\Admin\UpdateAdministratorRequest;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AdministratorController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        Gate::authorize('manage-administrators');
        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:200'],
            'role' => ['nullable', 'string'],
            'status' => ['nullable', 'in:active,inactive'],
        ]);
        $administrators = User::query()
            ->administrators()
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $query) use ($search): void {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when(
                isset($filters['role']) && in_array($filters['role'], $this->roleValues(), true),
                fn (Builder $query): Builder => $query->where('role', $filters['role']),
            )
            ->when(
                ($filters['status'] ?? null) === 'active',
                fn (Builder $query): Builder => $query->where('is_active', true),
            )
            ->when(
                ($filters['status'] ?? null) === 'inactive',
                fn (Builder $query): Builder => $query->where('is_active', false),
            )
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('admin.administrators.index', [
            'administrators' => $administrators,
            'filters' => $filters,
            'roles' => UserRole::administratorRoles(),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        Gate::authorize('manage-administrators');

        return view('admin.administrators.create', [
            'administrator' => null,
            'roles' => UserRole::administratorRoles(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreAdministratorRequest $request, AuditLogger $auditLogger): RedirectResponse
    {
        $administrator = User::query()->create([
            ...$request->safe()->only(['name', 'email', 'password', 'role']),
            'is_active' => $request->boolean('is_active'),
        ]);
        $auditLogger->record('administrator.created', $request->user(), $administrator, newValues: $administrator->only([
            'name',
            'email',
            'role',
            'is_active',
        ]));

        return to_route('admin.administrators.edit', $administrator)
            ->with('status', 'Administrator account created.');
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $administrator): View
    {
        Gate::authorize('manage-administrators');
        $this->ensureAdministrator($administrator);

        return view('admin.administrators.edit', [
            'administrator' => $administrator,
            'roles' => UserRole::administratorRoles(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        UpdateAdministratorRequest $request,
        User $administrator,
        AuditLogger $auditLogger,
    ): RedirectResponse {
        $this->ensureAdministrator($administrator);
        $requestedRole = UserRole::from($request->string('role')->toString());
        $requestedActive = $request->boolean('is_active');

        $data = $request->safe()->only(['name', 'email', 'password', 'role']);

        if (($data['password'] ?? null) === null) {
            unset($data['password']);
        }

        DB::transaction(function () use (
            $request,
            $administrator,
            $requestedActive,
            $requestedRole,
            $data,
            $auditLogger,
        ): void {
            $activeSuperAdministrators = User::query()
                ->where('role', UserRole::SuperAdmin->value)
                ->where('is_active', true)
                ->orderBy('id')
                ->lockForUpdate()
                ->get(['id']);
            $lockedAdministrator = User::query()->administrators()->whereKey($administrator->getKey())->lockForUpdate()->firstOrFail();

            if (
                $lockedAdministrator->is($request->user())
                && (! $requestedActive || $requestedRole !== UserRole::SuperAdmin)
            ) {
                throw ValidationException::withMessages([
                    'role' => 'You cannot deactivate or remove your own super administrator access.',
                ]);
            }

            if (
                $lockedAdministrator->isSuperAdmin()
                && $lockedAdministrator->is_active
                && (! $requestedActive || $requestedRole !== UserRole::SuperAdmin)
                && $activeSuperAdministrators->count() <= 1
            ) {
                throw ValidationException::withMessages([
                    'role' => 'At least one active super administrator is required.',
                ]);
            }

            $oldValues = $lockedAdministrator->only(['name', 'email', 'role', 'is_active']);
            $lockedAdministrator->update([
                ...$data,
                'is_active' => $requestedActive,
            ]);
            $auditLogger->record('administrator.updated', $request->user(), $lockedAdministrator, $oldValues, $lockedAdministrator->only([
                'name',
                'email',
                'role',
                'is_active',
            ]));
        });

        return back()->with('status', 'Administrator account updated.');
    }

    private function ensureAdministrator(User $administrator): void
    {
        abort_unless($administrator->isAdmin(), 404);
    }

    /** @return list<string> */
    private function roleValues(): array
    {
        return array_map(
            static fn (UserRole $role): string => $role->value,
            UserRole::administratorRoles(),
        );
    }
}
