<?php

namespace App\Http\Requests\Admin\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class MfaChallengeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->session()->has('admin_mfa.pending_user_id');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'digits:6'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => preg_replace('/\D/', '', (string) $this->input('code')),
        ]);
    }
}
