<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateFaqsRequest extends FormRequest
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:faqs,id'],
            'action' => ['required', 'string', Rule::in(['publish', 'unpublish', 'archive', 'category'])],
            // Only used with action=category; empty means "uncategorised".
            'faq_category_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
        ];
    }
}
