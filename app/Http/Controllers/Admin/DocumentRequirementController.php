<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveDocumentRequirementRequest;
use App\Models\DocumentRequirement;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class DocumentRequirementController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', DocumentRequirement::class);
        $requirements = DocumentRequirement::query()
            ->withCount('studentDocuments')
            ->orderByDesc('is_active')
            ->orderByRaw('due_date is null')
            ->orderBy('due_date')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);
        $editingRequirement = $request->integer('edit') > 0
            ? DocumentRequirement::query()->findOrFail($request->integer('edit'))
            : null;

        if ($editingRequirement !== null) {
            Gate::authorize('update', $editingRequirement);
        }

        $programs = User::query()->students()->whereNotNull('program')->distinct()->orderBy('program')->pluck('program');

        return view('admin.document-requirements.index', compact('requirements', 'editingRequirement', 'programs'));
    }

    public function store(SaveDocumentRequirementRequest $request): RedirectResponse
    {
        $requirement = DocumentRequirement::query()->create($request->validated());
        $this->auditLogger->record('document-requirement.created', $request->user(), $requirement, [], $requirement->attributesToArray());

        return to_route('admin.document_requirements.index')->with('status', 'Document requirement created.');
    }

    public function update(SaveDocumentRequirementRequest $request, DocumentRequirement $documentRequirement): RedirectResponse
    {
        $oldValues = $documentRequirement->attributesToArray();
        $documentRequirement->update($request->validated());
        $this->auditLogger->record('document-requirement.updated', $request->user(), $documentRequirement, $oldValues, $documentRequirement->fresh()->attributesToArray());

        return to_route('admin.document_requirements.index')->with('status', 'Document requirement updated.');
    }

    public function destroy(Request $request, DocumentRequirement $documentRequirement): RedirectResponse
    {
        Gate::authorize('delete', $documentRequirement);
        $oldValues = $documentRequirement->attributesToArray();
        $documentRequirement->delete();
        $this->auditLogger->record('document-requirement.deleted', $request->user(), $documentRequirement, $oldValues);

        return to_route('admin.document_requirements.index')->with('status', 'Document requirement deleted.');
    }
}
