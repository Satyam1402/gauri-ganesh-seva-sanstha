<?php

namespace App\Http\Requests\Admin;

use App\Enums\EnquiryStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateContactEnquiryStatusRequest extends FormRequest
{
    /**
     * Authorization is enforced by ContactEnquiryPolicy in the controller.
     */
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
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer', 'exists:contact_enquiries,id'],
            'status' => ['required', Rule::enum(EnquiryStatus::class)],
        ];
    }
}
