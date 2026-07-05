<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateDonationRequest extends FormRequest
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
            'amount' => ['required', 'numeric', 'min:1', 'max:999999999'],
            'payment_method' => ['required', 'string', 'in:razorpay,stripe,paypal,bank_transfer,upi'],
            'transaction_id' => ['nullable', 'string', 'max:191'],
            'payment_status' => ['required', 'string', 'in:pending,completed,failed,refunded'],
            'is_anonymous' => ['nullable', 'boolean'],
            'donated_at' => ['required', 'date'],
            'remarks' => ['nullable', 'string', 'max:2000'],
            'send_emails' => ['nullable', 'boolean'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'pan_number.regex' => 'The PAN must match the format ABCDE1234F.',
        ];
    }
}
