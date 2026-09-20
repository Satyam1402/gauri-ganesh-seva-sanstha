<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreBlogPostRequest extends FormRequest
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
            'blog_category_id' => ['nullable', 'integer', 'exists:blog_categories,id'],
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'title' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:blog_posts,slug'],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'allow_comments' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'is_featured' => ['nullable', 'boolean'],

            'featured_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            ...$this->seoRules(),
        ];
    }
}
