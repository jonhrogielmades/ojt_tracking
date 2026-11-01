<?php

namespace App\Http\Requests\Admin;

use App\Models\DocumentRequirement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class SaveDocumentRequirementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $requirement = $this->route('document_requirement');

        return $requirement instanceof DocumentRequirement
            ? ($this->user()?->can('update', $requirement) ?? false)
            : ($this->user()?->can('create', DocumentRequirement::class) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:5000'],
            'program' => ['nullable', 'string', 'max:255'],
            'due_date' => ['nullable', 'date'],
            'is_required' => ['required', 'boolean'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'category' => $this->filled('category') ? trim((string) $this->input('category')) : null,
            'program' => $this->filled('program') ? trim((string) $this->input('program')) : null,
        ]);
    }
}
