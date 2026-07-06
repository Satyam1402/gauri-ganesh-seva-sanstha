<?php

namespace App\Http\Requests\Admin;

use App\Enums\VolunteerApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateVolunteerApplicationStatusRequest extends FormRequest
{
    /**
     * Authorization is enforced by VolunteerApplicationPolicy in the controller.
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
            'ids.*' => ['integer', 'exists:volunteer_applications,id'],
            'status' => ['required', Rule::enum(VolunteerApplicationStatus::class)],
        ];
    }
}
