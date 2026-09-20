<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGalleryAlbumRequest extends FormRequest
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
            'gallery_category_id' => ['nullable', 'integer', 'exists:gallery_categories,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('gallery_albums', 'slug')->ignore($this->route('gallery_album'))],
            'description' => ['nullable', 'string', 'max:5000'],
            'event_date' => ['nullable', 'date'],
            'location' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'is_featured' => ['nullable', 'boolean'],
            'order_column' => ['nullable', 'integer', 'min:0'],

            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_cover_image' => ['nullable', 'boolean'],

            ...$this->seoRules(),
        ];
    }
}
