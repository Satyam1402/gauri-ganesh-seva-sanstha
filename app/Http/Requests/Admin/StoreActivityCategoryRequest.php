<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreActivityCategoryRequest extends FormRequest
{
    use HasSeoRules;

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
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:activity_categories,slug'],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            ...$this->seoRules(),
        ];
    }
}
