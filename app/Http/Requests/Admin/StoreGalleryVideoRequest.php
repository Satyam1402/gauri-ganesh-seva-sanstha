<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class StoreGalleryVideoRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:200'],
            'provider' => ['required', 'string', 'in:youtube,vimeo,self_hosted'],
            'video_url' => ['required_unless:provider,self_hosted', 'nullable', 'url', 'max:500'],
            'video_file' => ['required_if:provider,self_hosted', 'nullable', 'file', 'mimetypes:video/mp4,video/webm,video/ogg', 'max:102400'],
            'thumbnail' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'video_file.max' => 'Self-hosted videos must be 100 MB or smaller.',
        ];
    }
}
