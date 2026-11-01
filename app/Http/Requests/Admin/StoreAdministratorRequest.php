<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreAdministratorRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasCapability('administrators.manage') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')],
            'role' => ['required', Rule::in($this->administratorRoleValues())],
            'is_active' => ['required', 'boolean'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => Str::lower(trim((string) $this->input('email'))),
        ]);
    }

    /** @return list<string> */
    private function administratorRoleValues(): array
    {
        return array_map(
            static fn (UserRole $role): string => $role->value,
            UserRole::administratorRoles(),
        );
    }
}
