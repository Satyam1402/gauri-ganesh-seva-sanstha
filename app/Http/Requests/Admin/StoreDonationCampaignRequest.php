<?php

namespace App\Http\Requests\Admin;

use App\Http\Requests\Concerns\HasSeoRules;
use Illuminate\Foundation\Http\FormRequest;

class StoreDonationCampaignRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:200'],
            'slug' => ['nullable', 'string', 'max:220', 'unique:donation_campaigns,slug'],
            'short_description' => ['required', 'string', 'max:300'],
            'full_description' => ['required', 'string'],
            'goal_amount' => ['nullable', 'numeric', 'min:1', 'max:999999999'],
            'currency' => ['nullable', 'string', 'size:3'],
            'status' => ['required', 'string', 'in:draft,active,completed,archived'],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'is_featured' => ['nullable', 'boolean'],
            'order_column' => ['nullable', 'integer', 'min:0'],

            'featured_image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],

            ...$this->seoRules(),
        ];
    }
}
