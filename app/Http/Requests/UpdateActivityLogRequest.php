<?php

namespace App\Http\Requests;

use App\Models\ActivityLog;
use Illuminate\Contracts\Validation\ValidationRule;

class UpdateActivityLogRequest extends StoreActivityLogRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $activity = $this->route('activity');

        if ($activity instanceof ActivityLog && $activity->user_id !== $this->user()?->id) {
            abort(404);
        }

        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...parent::rules(),
            'remove_attachment' => ['sometimes', 'boolean'],
        ];
    }
}
