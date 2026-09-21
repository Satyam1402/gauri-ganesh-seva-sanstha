<?php

namespace App\Http\Requests\Admin;

use App\Enums\RestoreScope;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Restore is destructive: besides the permission, the admin must type the
 * confirmation word and re-enter their current password.
 */
class RestoreBackupRequest extends FormRequest
{
    public const CONFIRMATION = 'RESTORE';

    public function authorize(): bool
    {
        return $this->user()?->can('manage backups') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'scope' => ['required', Rule::enum(RestoreScope::class)],
            'confirmation' => ['required', 'string', Rule::in([self::CONFIRMATION])],
            'current_password' => ['required', 'current_password'],
            'acknowledge' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'confirmation.in' => 'Type '.self::CONFIRMATION.' exactly to confirm.',
            'current_password.current_password' => 'Your current password is incorrect.',
            'acknowledge.accepted' => 'Please acknowledge that existing data will be overwritten.',
        ];
    }
}
