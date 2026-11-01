<?php

namespace App\Http\Requests\Admin;

use App\Models\Announcement;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SaveAnnouncementRequest extends FormRequest
{
    public function authorize(): bool
    {
        $announcement = $this->route('announcement');

        return $announcement instanceof Announcement
            ? ($this->user()?->can('update', $announcement) ?? false)
            : ($this->user()?->can('create', Announcement::class) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:10000'],
            'audience' => ['required', Rule::in(['all', 'program', 'section'])],
            'audience_value' => ['nullable', 'required_unless:audience,all', 'string', 'max:255'],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'audience_value' => $this->input('audience') === 'all' ? null : trim((string) $this->input('audience_value')),
        ]);
    }
}
