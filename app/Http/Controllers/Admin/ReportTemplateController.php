<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SaveReportTemplateRequest;
use App\Models\ReportTemplate;
use App\Services\AuditLogger;
use App\Support\ActiveSlotConflict;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ReportTemplateController extends Controller
{
    public function __construct(private AuditLogger $auditLogger) {}

    public function index(Request $request): View
    {
        Gate::authorize('viewAny', ReportTemplate::class);
        $templates = ReportTemplate::query()
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->orderBy('id')
            ->paginate(15);
        $editingTemplate = $request->integer('edit') > 0
            ? ReportTemplate::query()->findOrFail($request->integer('edit'))
            : null;

        if ($editingTemplate !== null) {
            Gate::authorize('update', $editingTemplate);
        }

        return view('admin.report-templates.index', compact('templates', 'editingTemplate'));
    }

    public function store(SaveReportTemplateRequest $request): RedirectResponse
    {
        try {
            $template = DB::transaction(function () use ($request): ReportTemplate {
                if ($request->boolean('is_active')) {
                    ReportTemplate::query()->lockForUpdate()->get();
                    ReportTemplate::query()->update(['is_active' => false, 'active_slot' => null]);
                }

                return ReportTemplate::query()->create([
                    ...$request->validated(),
                    'active_slot' => $request->boolean('is_active') ? 1 : null,
                ]);
            });
        } catch (QueryException $exception) {
            if (! ActiveSlotConflict::matches($exception, 'report_templates_single_active', 'report_templates.active_slot')) {
                throw $exception;
            }

            return back()->withInput()->withErrors([
                'is_active' => 'Another administrator activated a report template at the same time. Refresh the page and try again.',
            ]);
        }
        $this->auditLogger->record('report-template.created', $request->user(), $template, [], $template->attributesToArray());

        return to_route('admin.report_templates.index')->with('status', 'Report template created.');
    }

    public function update(SaveReportTemplateRequest $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        $oldValues = $reportTemplate->attributesToArray();
        try {
            DB::transaction(function () use ($request, $reportTemplate): void {
                if ($request->boolean('is_active')) {
                    ReportTemplate::query()->lockForUpdate()->get();
                    ReportTemplate::query()->whereKeyNot($reportTemplate->id)->update(['is_active' => false, 'active_slot' => null]);
                }

                $reportTemplate->update([
                    ...$request->validated(),
                    'active_slot' => $request->boolean('is_active') ? 1 : null,
                ]);
            });
        } catch (QueryException $exception) {
            if (! ActiveSlotConflict::matches($exception, 'report_templates_single_active', 'report_templates.active_slot')) {
                throw $exception;
            }

            return back()->withInput()->withErrors([
                'is_active' => 'Another administrator activated a report template at the same time. Refresh the page and try again.',
            ]);
        }
        $this->auditLogger->record('report-template.updated', $request->user(), $reportTemplate, $oldValues, $reportTemplate->fresh()->attributesToArray());

        return to_route('admin.report_templates.index')->with('status', 'Report template updated.');
    }

    public function destroy(Request $request, ReportTemplate $reportTemplate): RedirectResponse
    {
        Gate::authorize('delete', $reportTemplate);

        if ($reportTemplate->is_active) {
            return back()->withErrors(['report_template' => 'Activate another template before deleting the current template.']);
        }

        $oldValues = $reportTemplate->attributesToArray();
        $reportTemplate->delete();
        $this->auditLogger->record('report-template.deleted', $request->user(), $reportTemplate, $oldValues);

        return to_route('admin.report_templates.index')->with('status', 'Report template deleted.');
    }
}
