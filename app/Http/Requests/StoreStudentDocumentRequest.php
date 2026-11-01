<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Query\Builder;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStudentDocumentRequest extends FormRequest
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
        return [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['nullable', 'string', 'max:100'],
            'document_requirement_id' => [
                'nullable',
                'integer',
                Rule::exists('document_requirements', 'id')->where(function (Builder $query): void {
                    $query->where('is_active', true)
                        ->where(function (Builder $query): void {
                            $query->whereNull('program');

                            if ($this->user()?->program !== null) {
                                $query->orWhere('program', $this->user()->program);
                            }
                        });
                }),
            ],
            'document' => [
                'required',
                'file',
                'mimes:pdf,jpg,jpeg,png,doc,docx',
                'extensions:pdf,jpg,jpeg,png,doc,docx',
                'max:10240',
            ],
        ];
    }
}
