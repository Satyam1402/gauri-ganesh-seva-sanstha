<?php

namespace App\Http\Requests\Admin;

use App\Enums\PartnerStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePartnerRequest extends FormRequest
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
            'partner_type_id' => ['nullable', 'integer', 'exists:partner_types,id'],
            'name' => ['required', 'string', 'max:150'],
            'slug' => ['nullable', 'string', 'max:170', 'alpha_dash', 'unique:partners,slug'],
            'short_description' => ['nullable', 'string', 'max:300'],
            'full_description' => ['nullable', 'string', 'max:10000'],
            // Only http(s) — blocks javascript:, data: and other schemes.
            'website_url' => ['nullable', 'string', 'max:255', 'url:http,https'],
            'email' => ['nullable', 'string', 'max:150', 'email:rfc'],
            'phone' => ['nullable', 'string', 'max:30', 'regex:/^[0-9+()\-\s]{6,30}$/'],
            'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'started_on' => ['nullable', 'date'],
            'ended_on' => ['nullable', 'date', 'after_or_equal:started_on'],
            'logo_alt' => ['nullable', 'string', 'max:150'],
            'status' => ['required', 'string', Rule::in(PartnerStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],

            // "image" content-sniffs the payload; mimes + dimensions guard
            // against SVG/animated tricks and absurd sizes. No SVG on purpose.
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=64,min_height=32,max_width=4000,max_height=4000'],
            'cover_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120', 'dimensions:min_width=300,min_height=150,max_width=6000,max_height=6000'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'website_url.url' => 'The website must be a full http:// or https:// address.',
            'phone.regex' => 'The phone number may only contain digits, spaces, +, - and brackets.',
            'ended_on.after_or_equal' => 'The end date cannot be before the start date.',
            'logo.dimensions' => 'The logo must be between 64×32 and 4000×4000 pixels.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'partner_type_id' => 'partnership type',
            'website_url' => 'website',
            'started_on' => 'start date',
            'ended_on' => 'end date',
        ];
    }
}
