<?php

namespace App\Http\Requests;

use App\Models\DtrEntry;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreWeeklySubmissionRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() instanceof User && $this->user()->isStudent();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'week_start' => ['required', 'date_format:Y-m-d', 'before_or_equal:'.now()->startOfWeek()->toDateString()],
            'summary' => ['required', 'string', 'max:10000'],
            'student_notes' => ['nullable', 'string', 'max:5000'],
            'dtr_entry_ids' => ['nullable', 'array', 'max:7'],
            'dtr_entry_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('dtr_entries', 'id')->where('user_id', $this->user()?->id),
            ],
            'intent' => ['required', Rule::in(['draft', 'submit'])],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                if ($validator->errors()->has('week_start')) {
                    return;
                }

                $weekStart = CarbonImmutable::createFromFormat('!Y-m-d', (string) $this->input('week_start'));

                if ($weekStart === false || $weekStart->dayOfWeekIso !== 1) {
                    $validator->errors()->add('week_start', 'The week must start on a Monday.');

                    return;
                }

                $entryIds = collect($this->input('dtr_entry_ids', []))
                    ->map(static fn (mixed $id): int => (int) $id);

                if ($this->input('intent') === 'submit' && $entryIds->isEmpty()) {
                    $validator->errors()->add('dtr_entry_ids', 'Select at least one DTR entry before submitting.');

                    return;
                }

                $entriesInWeek = DtrEntry::query()
                    ->where('user_id', $this->user()?->id)
                    ->whereKey($entryIds)
                    ->whereBetween('work_date', [$weekStart->toDateString(), $weekStart->endOfWeek()->toDateString()])
                    ->count();

                if ($entriesInWeek !== $entryIds->unique()->count()) {
                    $validator->errors()->add('dtr_entry_ids', 'Every selected DTR entry must belong to this week.');

                    return;
                }

                $allEntriesInWeek = DtrEntry::query()
                    ->where('user_id', $this->user()?->id)
                    ->whereBetween('work_date', [$weekStart->toDateString(), $weekStart->endOfWeek()->toDateString()])
                    ->count();

                if ($this->input('intent') === 'submit' && $entriesInWeek !== $allEntriesInWeek) {
                    $validator->errors()->add('dtr_entry_ids', 'Include every DTR entry from this week before submitting it for review.');
                }
            },
        ];
    }
}
