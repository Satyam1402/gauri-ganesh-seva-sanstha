<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateOrgProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'legal_name' => ['nullable', 'string', 'max:200'],
            'short_name' => ['nullable', 'string', 'max:100'],
            'registration_no' => ['nullable', 'string', 'max:100'],
            'registration_date' => ['nullable', 'date'],
            'pan_no' => ['nullable', 'string', 'max:20'],
            'trust_deed_no' => ['nullable', 'string', 'max:100'],
            'section_80g_no' => ['nullable', 'string', 'max:100'],
            'section_12a_no' => ['nullable', 'string', 'max:100'],
            'established_year' => ['nullable', 'digits:4', 'integer', 'min:1900', 'max:'.(date('Y'))],

            'new_certificates' => ['nullable', 'array'],
            'new_certificates.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_certificate_ids' => ['nullable', 'array'],
            'remove_certificate_ids.*' => ['integer', 'exists:media,id'],

            'new_documents' => ['nullable', 'array'],
            'new_documents.*' => ['file', 'mimes:jpg,jpeg,png,webp,pdf', 'max:5120'],
            'remove_document_ids' => ['nullable', 'array'],
            'remove_document_ids.*' => ['integer', 'exists:media,id'],
        ];
    }
}
