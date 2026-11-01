<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SavePartnerCompanyRequest;
use App\Models\PartnerCompany;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PartnerCompanyController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', PartnerCompany::class);
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:200']]);
        $companies = PartnerCompany::query()
            ->withCount('studentProfiles')
            ->when($filters['q'] ?? null, function (Builder $query, string $search): void {
                $query->where(fn (Builder $query) => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('address', 'like', "%{$search}%"));
            })
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15)
            ->withQueryString();
        $editingCompany = $request->integer('edit') > 0
            ? PartnerCompany::query()->findOrFail($request->integer('edit'))
            : null;

        if ($editingCompany !== null) {
            Gate::authorize('update', $editingCompany);
        }

        return view('admin.partner-companies.index', compact('companies', 'editingCompany', 'filters'));
    }

    public function store(SavePartnerCompanyRequest $request): RedirectResponse
    {
        $company = PartnerCompany::query()->create($this->payload($request));
        $this->auditLogger->record('partner-company.created', $request->user(), $company, [], $company->attributesToArray());

        return to_route('admin.partner_companies.index')->with('status', 'Partner company added.');
    }

    public function update(SavePartnerCompanyRequest $request, PartnerCompany $partnerCompany): RedirectResponse
    {
        $oldValues = $partnerCompany->attributesToArray();
        $partnerCompany->update($this->payload($request));
        $this->auditLogger->record('partner-company.updated', $request->user(), $partnerCompany, $oldValues, $partnerCompany->fresh()->attributesToArray());

        return to_route('admin.partner_companies.index')->with('status', 'Partner company updated.');
    }

    public function destroy(Request $request, PartnerCompany $partnerCompany): RedirectResponse
    {
        Gate::authorize('delete', $partnerCompany);
        $oldValues = $partnerCompany->attributesToArray();
        $partnerCompany->delete();
        $this->auditLogger->record('partner-company.deleted', $request->user(), $partnerCompany, $oldValues);

        return to_route('admin.partner_companies.index')->with('status', 'Partner company removed.');
    }

    /** @return array<string, mixed> */
    private function payload(SavePartnerCompanyRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => $validated['name'],
            'address' => $validated['address'] ?? null,
            'contact_name' => $validated['contact_name'] ?? null,
            'contact_email' => $validated['contact_email'] ?? null,
            'contact_phone' => $validated['contact_phone'] ?? null,
            'departments' => $validated['departments'],
            'notes' => $validated['notes'] ?? null,
            'is_active' => $request->boolean('is_active'),
        ];
    }
}
