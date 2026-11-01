<?php

namespace App\Http\Requests;

use App\Models\DtrEntry;
use App\Support\StudentWorkDateRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreDtrRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return self::rulesFor((int) $this->user()->id);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rulesFor(
        int $userId,
        ?DtrEntry $ignoredDtr = null,
        bool $enforceUniqueDate = true,
        bool $requireIdempotencyKey = false,
    ): array {
        $workDateRules = [
            'required',
            'date_format:Y-m-d',
            ...StudentWorkDateRules::for($userId),
        ];

        if ($enforceUniqueDate) {
            $uniqueWorkDate = Rule::unique('dtr_entries', 'work_date')
                ->where(fn (Builder $query): Builder => $query->where('user_id', $userId));

            if ($ignoredDtr !== null) {
                $uniqueWorkDate->ignore($ignoredDtr->getKey());
            }

            $workDateRules[] = $uniqueWorkDate;
        }

        return [
            'work_date' => $workDateRules,
            'time_in' => ['required', 'date_format:H:i'],
            'break_start' => ['nullable', 'required_with:break_end', 'date_format:H:i'],
            'break_end' => ['nullable', 'required_with:break_start', 'date_format:H:i'],
            'time_out' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:2000'],
            'idempotency_key' => [$requireIdempotencyKey ? 'required' : 'nullable', 'uuid'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                self::addChronologyErrors($validator, $this->all());
            },
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function addChronologyErrors(Validator $validator, array $data): void
    {
        if ($validator->errors()->hasAny(['time_in', 'break_start', 'break_end', 'time_out'])) {
            return;
        }

        $timeIn = (string) ($data['time_in'] ?? '');
        $timeOut = (string) ($data['time_out'] ?? '');

        if ($timeIn >= $timeOut) {
            $validator->errors()->add('time_out', 'Time out must be later than time in.');

            return;
        }

        $breakStart = $data['break_start'] ?? null;
        $breakEnd = $data['break_end'] ?? null;

        if ($breakStart === null || $breakStart === '' || $breakEnd === null || $breakEnd === '') {
            return;
        }

        if (! ($timeIn < $breakStart && $breakStart < $breakEnd && $breakEnd < $timeOut)) {
            $validator->errors()->add(
                'break_start',
                'The break must start after time in, end before time out, and have a positive duration.',
            );
        }
    }
}
