<?php

namespace App\Http\Requests\Admin;

use App\Enums\FaqStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFaqRequest extends FormRequest
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
            'faq_category_id' => ['nullable', 'integer', 'exists:faq_categories,id'],
            'question' => ['required', 'string', 'min:5', 'max:255'],
            // Markdown source. Raw HTML is stripped at render time, so no
            // tag whitelist is needed here — just a sane length cap.
            'answer' => ['required', 'string', 'min:10', 'max:10000'],
            'status' => ['required', 'string', Rule::in(FaqStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'published_at' => ['nullable', 'date'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'question.min' => 'The question should be at least 5 characters long.',
            'answer.min' => 'The answer should be at least 10 characters long.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'faq_category_id' => 'category',
        ];
    }
}
