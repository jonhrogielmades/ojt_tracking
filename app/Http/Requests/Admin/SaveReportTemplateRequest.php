<?php

namespace App\Http\Requests\Admin;

use App\Models\ReportTemplate;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveReportTemplateRequest extends FormRequest
{
    public function authorize(): bool
    {
        $reportTemplate = $this->route('report_template');

        return $reportTemplate instanceof ReportTemplate
            ? ($this->user()?->can('update', $reportTemplate) ?? false)
            : ($this->user()?->can('create', ReportTemplate::class) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'institution_name' => ['required', 'string', 'max:255'],
            'office_name' => ['nullable', 'string', 'max:255'],
            'monthly_title' => ['required', 'string', 'max:255'],
            'weekly_title' => ['required', 'string', 'max:255'],
            'prepared_by_label' => ['required', 'string', 'max:100'],
            'approved_by_label' => ['required', 'string', 'max:100'],
            'signatory_name' => ['nullable', 'string', 'max:255'],
            'signatory_title' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
