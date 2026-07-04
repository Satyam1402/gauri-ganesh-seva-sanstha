<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeSectionRequest extends FormRequest
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
            'heading' => ['nullable', 'string', 'max:200'],
            'subheading' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],

            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_image' => ['nullable', 'boolean'],
            'background_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_background_image' => ['nullable', 'boolean'],

            'buttons' => ['nullable', 'array'],
            'buttons.*.label' => ['nullable', 'string', 'max:60'],
            'buttons.*.url' => ['nullable', 'string', 'max:255'],
            'buttons.*.variant' => ['nullable', 'string', 'in:primary,accent,secondary,ghost,danger'],

            'items' => ['nullable', 'array'],
            'items.*.id' => ['nullable', 'integer', 'exists:home_section_items,id'],
            'items.*.title' => ['nullable', 'string', 'max:150'],
            'items.*.subtitle' => ['nullable', 'string', 'max:200'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.icon' => ['nullable', 'string', 'max:50'],
            'items.*.link_url' => ['nullable', 'string', 'max:255'],
            'items.*.is_active' => ['nullable', 'boolean'],
            'items.*.image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ];
    }
}
