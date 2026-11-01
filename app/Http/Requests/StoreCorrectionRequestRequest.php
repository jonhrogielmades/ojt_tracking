<?php

namespace App\Http\Requests;

use App\Models\DtrEntry;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreCorrectionRequestRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $dtr = $this->route('dtr');

        if ($dtr instanceof DtrEntry && $dtr->user_id !== $this->user()?->id) {
            abort(404);
        }

        return $this->user() instanceof User
            && $this->user()->isStudent()
            && $dtr instanceof DtrEntry
            && $dtr->loadMissing('weeklySubmission:id,status')->canRequestCorrection();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:20', 'max:2000'],
        ];
    }
}
