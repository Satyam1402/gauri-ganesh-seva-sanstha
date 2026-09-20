<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBlogPostRequest extends FormRequest
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
            'slug' => ['nullable', 'string', 'max:220', Rule::unique('blog_posts', 'slug')->ignore($this->route('blog_post'))],
            'excerpt' => ['required', 'string', 'max:500'],
            'content' => ['required', 'string'],
            'published_at' => ['nullable', 'date'],
            'tags' => ['nullable', 'string', 'max:500'],
            'allow_comments' => ['nullable', 'boolean'],
            'status' => ['required', 'string', 'in:draft,published,archived'],
            'is_featured' => ['nullable', 'boolean'],

            'featured_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_featured_image' => ['nullable', 'boolean'],
            'gallery' => ['nullable', 'array'],
            'gallery.*' => ['image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_gallery_ids' => ['nullable', 'array'],
            'remove_gallery_ids.*' => ['integer'],

            ...$this->seoRules(),
        ];
    }
}
