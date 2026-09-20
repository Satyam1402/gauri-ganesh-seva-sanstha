<?php

namespace App\Http\Requests\Admin;

use App\Enums\TestimonialStatus;
use App\Enums\TestimonialType;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateTestimonialRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:150'],
            'designation' => ['nullable', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'location' => ['nullable', 'string', 'max:150'],
            'content' => ['required', 'string', 'min:20', 'max:2000'],
            'rating' => ['nullable', 'integer', 'min:'.Testimonial::MIN_RATING, 'max:'.Testimonial::MAX_RATING],
            'type' => ['required', 'string', Rule::in(TestimonialType::values())],
            'status' => ['required', 'string', Rule::in(TestimonialStatus::values())],
            'is_featured' => ['nullable', 'boolean'],
            'display_order' => ['nullable', 'integer', 'min:0', 'max:65535'],
            'published_at' => ['nullable', 'date'],
            'related' => ['nullable', 'string', 'regex:/^(activity|campaign):\d+$/'],
            'consent_given' => ['nullable', 'boolean'],
            'consented_at' => ['nullable', 'date', 'before_or_equal:today'],
            'admin_notes' => ['nullable', 'string', 'max:2000'],

            // "image" already rejects non-image payloads by content sniffing;
            // mimes + dimensions guard against SVG/animated tricks and huge files.
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:min_width=80,min_height=80,max_width=4000,max_height=4000'],
            'remove_profile_photo' => ['nullable', 'boolean'],
        ];
    }

    /**
     * The "related" select value must point at a real activity/campaign.
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $value = $this->input('related');

            if (empty($value) || $validator->errors()->has('related')) {
                return;
            }

            [$prefix, $id] = explode(':', $value, 2);
            $class = TestimonialService::RELATED_TYPES[$prefix] ?? null;

            if ($class === null || ! $class::query()->whereKey((int) $id)->exists()) {
                $validator->errors()->add('related', 'The selected activity or campaign no longer exists.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'content.min' => 'The testimonial should be at least 20 characters long.',
            'consented_at.before_or_equal' => 'The consent date cannot be in the future.',
            'profile_photo.dimensions' => 'The profile photo must be between 80×80 and 4000×4000 pixels.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'related' => 'related activity or campaign',
            'consented_at' => 'consent date',
        ];
    }
}
