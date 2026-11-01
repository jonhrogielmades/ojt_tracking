<?php

namespace App\Http\Requests\Admin;

use App\Models\StudentDocument;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReviewStudentDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        $document = $this->route('document');

        return $document instanceof StudentDocument && ($this->user()?->can('review', $document) ?? false);
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        return [
            'review_status' => ['required', Rule::in(['approved', 'rejected'])],
            'review_notes' => ['nullable', 'required_if:review_status,rejected', 'string', 'max:5000'],
        ];
    }
}
