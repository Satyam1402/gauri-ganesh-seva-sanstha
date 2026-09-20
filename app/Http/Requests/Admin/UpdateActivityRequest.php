<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateActivityRequest extends FormRequest
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
            'activity_category_id' => ['required', 'integer', 'exists:activity_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('activities', 'slug')->ignore($this->route('activity'))],
            'short_description' => ['required', 'string', 'max:300'],
            'full_description' => ['required', 'string'],
            'activity_date' => ['required', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'organizer' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'is_featured' => ['nullable', 'boolean'],

            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer', 'exists:media,id'],

            ...$this->seoRules(),
        ];
    }
}
