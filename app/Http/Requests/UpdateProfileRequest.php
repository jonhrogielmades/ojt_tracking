<?php

namespace App\Http\Requests;

use App\Models\AcademicTerm;
use App\Models\StudentProfile;
use App\Models\User;
use App\Rules\StudentId;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateProfileRequest extends FormRequest
{
    private ?AcademicTerm $resolvedAcademicTerm = null;

    private bool $academicTermResolved = false;

    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isStudent();
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()?->getAuthIdentifier();
        $currentPartnerCompanyId = $userId !== null
            ? StudentProfile::query()->where('user_id', $userId)->value('partner_company_id')
            : null;

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'student_id' => ['required', 'string', 'max:9', new StudentId, Rule::unique('users', 'student_id')->ignore($userId)],
            'program' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:100'],
            'partner_company_id' => [
                'nullable',
                'integer',
                Rule::exists('partner_companies', 'id')->where(function (Builder $query) use ($currentPartnerCompanyId): void {
                    $query->where('is_active', true);

                    if ($currentPartnerCompanyId !== null) {
                        $query->orWhere('id', $currentPartnerCompanyId);
                    }
                }),
            ],
            'company_name' => ['nullable', 'string', 'max:255'],
            'department' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:1000'],
            'supervisor_name' => ['nullable', 'string', 'max:255'],
            'supervisor_email' => ['nullable', 'email', 'max:255'],
            'supervisor_phone' => ['nullable', 'string', 'max:50'],
            'ojt_start_date' => ['nullable', 'date_format:Y-m-d'],
            'ojt_end_date' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:ojt_start_date'],
            'work_days' => ['nullable', 'array', 'max:7'],
            'work_days.*' => ['integer', 'between:1,7', 'distinct'],
            'scheduled_time_in' => ['nullable', 'required_with:scheduled_time_out', 'date_format:H:i'],
            'scheduled_time_out' => ['nullable', 'required_with:scheduled_time_in', 'date_format:H:i', 'after:scheduled_time_in'],
            'schedule_notes' => ['nullable', 'string', 'max:1000'],
            'required_hours' => ['nullable', 'numeric', 'min:0.25', 'max:10000'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
            'student_id' => trim((string) $this->input('student_id')),
        ]);
    }

    public function academicTerm(): ?AcademicTerm
    {
        if (! $this->academicTermResolved) {
            $this->resolvedAcademicTerm = AcademicTerm::query()
                ->active()
                ->orderByDesc('starts_on')
                ->orderByDesc('id')
                ->first();
            $this->academicTermResolved = true;
        }

        return $this->resolvedAcademicTerm;
    }

    /** @return array<int, callable(Validator): void> */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->hasAny(['ojt_start_date', 'ojt_end_date'])) {
                    return;
                }

                $user = $this->user();

                if (! $user instanceof User) {
                    return;
                }

                $recordedDates = collect([
                    $user->dtrEntries()->min('work_date'),
                    $user->dtrEntries()->max('work_date'),
                    $user->activityLogs()->min('activity_date'),
                    $user->activityLogs()->max('activity_date'),
                ])->filter()->map(static fn (mixed $date): CarbonImmutable => CarbonImmutable::parse((string) $date)->startOfDay());

                if ($recordedDates->isEmpty()) {
                    return;
                }

                $academicTerm = $this->academicTerm();
                $startDate = $this->normalizedDate($this->input('ojt_start_date'))
                    ?? $academicTerm?->starts_on?->toImmutable()->startOfDay();
                $endDate = $this->normalizedDate($this->input('ojt_end_date'))
                    ?? $academicTerm?->ends_on?->toImmutable()->startOfDay();

                if ($startDate?->isAfter($recordedDates->min())) {
                    $validator->errors()->add(
                        'ojt_start_date',
                        'The OJT start date must include your earliest saved DTR or activity.',
                    );
                }

                if ($endDate?->isBefore($recordedDates->max())) {
                    $validator->errors()->add(
                        'ojt_end_date',
                        'The OJT end date must include your latest saved DTR or activity.',
                    );
                }
            },
        ];
    }

    private function normalizedDate(mixed $value): ?CarbonInterface
    {
        if (! is_string($value) || $value === '') {
            return null;
        }

        return CarbonImmutable::createFromFormat('!Y-m-d', $value);
    }
}
