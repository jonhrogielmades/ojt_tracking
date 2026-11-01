<?php

namespace App\Http\Requests;

use App\Support\StudentWorkDateRules;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityLogRequest extends FormRequest
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
        return self::rulesFor(userId: (int) $this->user()->id);
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public static function rulesFor(bool $offlineSync = false, ?int $userId = null): array
    {
        return [
            'activity_date' => [
                'required',
                'date_format:Y-m-d',
                ...($userId === null ? ['before_or_equal:'.today()->toDateString()] : StudentWorkDateRules::for($userId)),
            ],
            'tasks_completed' => ['required', 'string', 'max:10000'],
            'skills_tools' => ['nullable', 'string', 'max:5000'],
            'lessons_learned' => ['nullable', 'string', 'max:10000'],
            'challenges_notes' => ['nullable', 'string', 'max:10000'],
            'attachment' => $offlineSync
                ? ['prohibited']
                : ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,doc,docx', 'extensions:pdf,jpg,jpeg,png,doc,docx', 'max:10240'],
            'idempotency_key' => [$offlineSync ? 'required' : 'nullable', 'uuid'],
        ];
    }
}
