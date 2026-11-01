<?php

namespace App\Http\Requests\Admin;

use App\Models\PartnerCompany;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SavePartnerCompanyRequest extends FormRequest
{
    public function authorize(): bool
    {
        $partnerCompany = $this->route('partner_company');

        return $partnerCompany instanceof PartnerCompany
            ? ($this->user()?->can('update', $partnerCompany) ?? false)
            : ($this->user()?->can('create', PartnerCompany::class) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', Rule::unique('partner_companies', 'name')->ignore($this->route('partner_company'))],
            'address' => ['nullable', 'string', 'max:2000'],
            'contact_name' => ['nullable', 'string', 'max:255'],
            'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'],
            'departments_text' => ['nullable', 'string', 'max:5000'],
            'departments' => ['array', 'max:100'],
            'departments.*' => ['string', 'max:255', 'distinct'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $departments = collect(preg_split('/\r\n|\r|\n/', (string) $this->input('departments_text')) ?: [])
            ->map(static fn (string $department): string => trim($department))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'contact_email' => $this->filled('contact_email') ? Str::lower(trim((string) $this->input('contact_email'))) : null,
            'departments' => $departments,
        ]);
    }
}
