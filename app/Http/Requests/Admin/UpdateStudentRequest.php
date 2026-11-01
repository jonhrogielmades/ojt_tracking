<?php

namespace App\Http\Requests\Admin;

use App\Models\User;
use App\Rules\StudentId;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateStudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $student = $this->route('student');

        return $student instanceof User && ($this->user()?->can('update', $student) ?? false);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        /** @var User|null $student */
        $student = $this->route('student');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($student)],
            'student_id' => ['required', 'string', 'max:9', new StudentId, Rule::unique('users', 'student_id')->ignore($student)],
            'program' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:100'],
            'password' => ['nullable', 'string', 'max:255', 'confirmed', Password::defaults()],
            'is_active' => ['required', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => trim((string) $this->input('name')),
            'email' => Str::lower(trim((string) $this->input('email'))),
            'student_id' => trim((string) $this->input('student_id')),
            'program' => trim((string) $this->input('program')),
            'section' => trim((string) $this->input('section')),
        ]);
    }
}
