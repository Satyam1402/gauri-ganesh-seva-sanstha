<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreEventRequest extends FormRequest
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
            'event_category_id' => ['nullable', 'integer', 'exists:event_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:events,slug'],
            'short_description' => ['required', 'string', 'max:300'],
            'full_description' => ['required', 'string'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'venue' => ['nullable', 'string', 'max:150'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'map_url' => ['nullable', 'url', 'max:500'],
            'organizer' => ['nullable', 'string', 'max:150'],
            'max_participants' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'requires_registration' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:draft,published,cancelled,completed'],
            'is_featured' => ['nullable', 'boolean'],

            'featured_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            ...$this->seoRules(),
        ];
    }
}
