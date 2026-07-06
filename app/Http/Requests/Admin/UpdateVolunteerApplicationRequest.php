<?php

namespace App\Http\Requests\Admin;

use App\Enums\VolunteerApplicationStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVolunteerApplicationRequest extends FormRequest
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
            'status' => ['required', Rule::enum(VolunteerApplicationStatus::class)],
            'admin_notes' => ['nullable', 'string', 'max:5000'],
        ];
    }
}
