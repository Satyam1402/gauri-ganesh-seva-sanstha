<?php

namespace App\Http\Requests\Frontend;

use App\Enums\CommunicationMethod;
use App\Enums\Gender;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreVolunteerApplicationRequest extends FormRequest
{
    /**
     * Minimum applicant age in years.
     */
    private const MIN_AGE = 16;

    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $phoneRegex = 'regex:/^[0-9+\-\s()]{7,20}$/';

        return [
            // Personal details
            'first_name' => ['required', 'string', 'max:100'],
            'last_name' => ['required', 'string', 'max:100'],
            'gender' => ['required', Rule::enum(Gender::class)],
            'date_of_birth' => [
                'required', 'date',
                'before_or_equal:'.now()->subYears(self::MIN_AGE)->toDateString(),
                'after:'.now()->subYears(100)->toDateString(),
            ],

            // Contact
            'email' => [
                'required', 'email', 'max:150',
                // One open application per email — approved or rejected
                // applicants may apply again later.
                Rule::unique('volunteer_applications', 'email')
                    ->whereIn('status', VolunteerApplicationStatus::openValues())
                    ->whereNull('deleted_at'),
            ],
            'phone' => ['required', 'string', 'max:20', $phoneRegex],
            'alternate_phone' => ['nullable', 'string', 'max:20', $phoneRegex, 'different:phone'],

            // Address
            'address' => ['required', 'string', 'max:500'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'max:100'],
            'country' => ['required', 'string', 'max:100'],
            'pin_code' => ['required', 'string', 'regex:/^[A-Za-z0-9][A-Za-z0-9\- ]{2,10}$/'],

            // Professional background
            'occupation' => ['required', 'string', 'max:150'],
            'organization' => ['nullable', 'string', 'max:150'],
            'skills' => ['required', 'string', 'max:2000'],
            'experience' => ['nullable', 'string', 'max:5000'],

            // Volunteering preferences
            'areas_of_interest' => ['required', 'array', 'min:1'],
            'areas_of_interest.*' => [Rule::in(array_keys(config('volunteers.areas_of_interest', [])))],
            'availability' => ['required', Rule::enum(VolunteerAvailability::class)],

            // Safety & wellbeing
            'emergency_contact_name' => ['required', 'string', 'max:150'],
            'emergency_contact_phone' => ['required', 'string', 'max:20', $phoneRegex, 'different:phone'],
            'medical_information' => ['nullable', 'string', 'max:2000'],

            'message' => ['nullable', 'string', 'max:2000'],
            'preferred_communication_method' => ['required', Rule::enum(CommunicationMethod::class)],
            'consent' => ['accepted'],

            // Uploads
            'profile_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'identity_proof' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:4096'],
            'resume' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:4096'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date_of_birth.before_or_equal' => 'Volunteers must be at least '.self::MIN_AGE.' years old.',
            'date_of_birth.after' => 'Please enter a valid date of birth.',
            'email.unique' => 'An application with this email address is already under review. We will get back to you soon!',
            'phone.regex' => 'Please enter a valid phone number.',
            'alternate_phone.regex' => 'Please enter a valid alternate phone number.',
            'alternate_phone.different' => 'The alternate phone must differ from your primary phone.',
            'emergency_contact_phone.regex' => 'Please enter a valid emergency contact number.',
            'emergency_contact_phone.different' => 'The emergency contact number must differ from your own phone.',
            'pin_code.regex' => 'Please enter a valid PIN / postal code.',
            'areas_of_interest.required' => 'Please choose at least one area of interest.',
            'consent.accepted' => 'You must agree to the volunteer terms before submitting.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'pin_code' => 'PIN code',
            'areas_of_interest' => 'areas of interest',
            'preferred_communication_method' => 'preferred communication method',
        ];
    }
}
