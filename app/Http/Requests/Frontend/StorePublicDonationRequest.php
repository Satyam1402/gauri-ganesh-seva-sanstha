<?php

namespace App\Http\Requests\Frontend;

use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePublicDonationRequest extends FormRequest
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
            'donation_campaign_id' => ['nullable', 'integer', 'exists:donation_campaigns,id'],
            'donor_name' => ['required', 'string', 'max:150'],
            'donor_email' => ['required', 'email', 'max:150'],
            'donor_phone' => ['nullable', 'string', 'max:20'],
            'donor_address' => ['nullable', 'string', 'max:500'],
            'pan_number' => ['nullable', 'string', 'regex:/^[A-Za-z]{5}[0-9]{4}[A-Za-z]$/'],
            'amount' => [
                'required',
                'numeric',
                'min:'.config('donations.min_amount'),
                'max:'.config('donations.max_amount'),
            ],
            'payment_method' => [
                'required',
                'string',
                // Donors may only pick gateways that are enabled and configured.
                Rule::in(array_keys(app(PaymentGatewayManager::class)->enabledOptions())),
            ],
            'is_anonymous' => ['nullable', 'boolean'],
            'remarks' => ['nullable', 'string', 'max:1000'],

            // Honeypot — bots fill every field; humans never see this one.
            'website' => ['prohibited'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pan_number.regex' => 'The PAN must match the format ABCDE1234F.',
            'payment_method.in' => 'Please choose one of the available payment methods.',
            'website.prohibited' => 'Submission rejected.',
        ];
    }
}
