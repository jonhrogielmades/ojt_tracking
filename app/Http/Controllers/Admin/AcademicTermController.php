<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveAcademicTermRequest;
use App\Models\AcademicTerm;
use App\Services\AuditLogger;
use App\Support\ActiveSlotConflict;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class AcademicTermController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', AcademicTerm::class);
        $terms = AcademicTerm::query()
            ->orderByDesc('is_active')
            ->orderByDesc('starts_on')
            ->orderByDesc('id')
            ->paginate(15);
        $editingTerm = $request->integer('edit') > 0
            ? AcademicTerm::query()->findOrFail($request->integer('edit'))
            : null;

        if ($editingTerm !== null) {
            Gate::authorize('update', $editingTerm);
        }

        return view('admin.academic-terms.index', compact('terms', 'editingTerm'));
    }

    public function store(SaveAcademicTermRequest $request): RedirectResponse
    {
        try {
            $term = DB::transaction(function () use ($request): AcademicTerm {
                if ($request->boolean('is_active')) {
                    AcademicTerm::query()->lockForUpdate()->get();
                    AcademicTerm::query()->update(['is_active' => false, 'active_slot' => null]);
                }

                return AcademicTerm::query()->create($this->payload($request));
            });
        } catch (QueryException $exception) {
            if (! ActiveSlotConflict::matches($exception, 'academic_terms_single_active', 'academic_terms.active_slot')) {
                throw $exception;
            }

            return back()->withInput()->withErrors([
                'is_active' => 'Another administrator activated an academic term at the same time. Refresh the page and try again.',
            ]);
        }
        $this->auditLogger->record('academic-term.created', $request->user(), $term, [], $term->attributesToArray());

        return to_route('admin.academic_terms.index')->with('status', "Academic term {$term->code} created.");
    }

    public function update(SaveAcademicTermRequest $request, AcademicTerm $academicTerm): RedirectResponse
    {
        $oldValues = $academicTerm->attributesToArray();
        try {
            DB::transaction(function () use ($request, $academicTerm): void {
                if ($request->boolean('is_active')) {
                    AcademicTerm::query()->lockForUpdate()->get();
                    AcademicTerm::query()->whereKeyNot($academicTerm->id)->update(['is_active' => false, 'active_slot' => null]);
                }

                $academicTerm->update($this->payload($request));
            });
        } catch (QueryException $exception) {
            if (! ActiveSlotConflict::matches($exception, 'academic_terms_single_active', 'academic_terms.active_slot')) {
                throw $exception;
            }

            return back()->withInput()->withErrors([
                'is_active' => 'Another administrator activated an academic term at the same time. Refresh the page and try again.',
            ]);
        }
        $this->auditLogger->record('academic-term.updated', $request->user(), $academicTerm, $oldValues, $academicTerm->fresh()->attributesToArray());

        return to_route('admin.academic_terms.index')->with('status', 'Academic term updated.');
    }

    public function destroy(Request $request, AcademicTerm $academicTerm): RedirectResponse
    {
        Gate::authorize('delete', $academicTerm);

        if ($academicTerm->is_active) {
            return back()->withErrors(['academic_term' => 'Activate another term before deleting the current active term.']);
        }

        $oldValues = $academicTerm->attributesToArray();
        $academicTerm->delete();
        $this->auditLogger->record('academic-term.deleted', $request->user(), $academicTerm, $oldValues);

        return to_route('admin.academic_terms.index')->with('status', 'Academic term deleted.');
    }

    /** @return array<string, mixed> */
    private function payload(SaveAcademicTermRequest $request): array
    {
        $validated = $request->validated();

        return [
            'name' => $validated['name'],
            'code' => $validated['code'],
            'starts_on' => $validated['starts_on'],
            'ends_on' => $validated['ends_on'],
            'required_minutes' => (int) round((float) $validated['required_hours'] * 60),
            'programs' => $validated['programs'],
            'sections' => $validated['sections'],
            'holidays' => $validated['holidays'],
            'is_active' => $request->boolean('is_active'),
            'active_slot' => $request->boolean('is_active') ? 1 : null,
        ];
    }
}
