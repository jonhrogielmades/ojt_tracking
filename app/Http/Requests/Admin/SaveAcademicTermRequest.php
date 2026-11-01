<?php

namespace App\Http\Requests\Admin;

use App\Models\AcademicTerm;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SaveAcademicTermRequest extends FormRequest
{
    public function authorize(): bool
    {
        $academicTerm = $this->route('academic_term');

        return $academicTerm instanceof AcademicTerm
            ? ($this->user()?->can('update', $academicTerm) ?? false)
            : ($this->user()?->can('create', AcademicTerm::class) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', Rule::unique('academic_terms', 'code')->ignore($this->route('academic_term'))],
            'starts_on' => ['required', 'date'],
            'ends_on' => ['required', 'date', 'after_or_equal:starts_on'],
            'required_hours' => ['required', 'numeric', 'min:1', 'max:10000'],
            'programs_text' => ['nullable', 'string', 'max:5000'],
            'sections_text' => ['nullable', 'string', 'max:5000'],
            'holidays_text' => ['nullable', 'string', 'max:10000'],
            'programs' => ['array', 'max:100'],
            'programs.*' => ['string', 'max:255', 'distinct'],
            'sections' => ['array', 'max:200'],
            'sections.*' => ['string', 'max:100', 'distinct'],
            'holidays' => ['array', 'max:100'],
            'holidays.*.date' => ['required', 'date_format:Y-m-d'],
            'holidays.*.name' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $holidayLines = $this->lines((string) $this->input('holidays_text'));

        $this->merge([
            'name' => trim((string) $this->input('name')),
            'code' => Str::upper(trim((string) $this->input('code'))),
            'programs' => $this->lines((string) $this->input('programs_text')),
            'sections' => $this->lines((string) $this->input('sections_text')),
            'holidays' => array_map(static function (string $line): array {
                [$date, $name] = array_pad(explode('|', $line, 2), 2, 'Holiday');

                return ['date' => trim($date), 'name' => trim($name)];
            }, $holidayLines),
        ]);
    }

    /** @return list<string> */
    private function lines(string $value): array
    {
        return collect(preg_split('/\r\n|\r|\n/', $value) ?: [])
            ->map(static fn (string $line): string => trim($line))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }
}
