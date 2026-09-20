<?php

namespace App\Http\Requests\Admin;

use App\Support\SettingsRegistry;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validation for one settings tab, generated from the registry so the
 * rules can never drift from the form.
 */
class UpdateSettingsRequest extends FormRequest
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
        $rules = [];

        foreach (SettingsRegistry::fields($this->route('group')) as $key => $field) {
            $rules[$key] = $this->rulesFor($field);

            if (SettingsRegistry::isMedia($field)) {
                $rules['remove_'.$key] = ['nullable', 'boolean'];
            }
        }

        return $rules;
    }

    /**
     * @param  array<string, mixed>  $field
     * @return list<mixed>
     */
    private function rulesFor(array $field): array
    {
        $rules = $field['rules'] ?? ['nullable'];

        return match ($field['type']) {
            // Selects are validated against their own option list; the
            // timezone list is generated, so it uses the timezone rule.
            'select' => array_merge($rules, $field['options'] === 'timezones' ? [] : [Rule::in(array_keys($field['options']))]),
            'boolean' => ['nullable', 'boolean'],
            'integer' => array_values(array_unique(array_merge(['nullable', 'integer'], $rules))),
            'decimal' => array_values(array_unique(array_merge(['nullable', 'numeric'], $rules))),
            'email' => array_values(array_unique(array_merge(['nullable', 'email:rfc'], $rules))),
            'url' => array_values(array_unique(array_merge(['nullable', 'url:http,https'], $rules))),
            'color' => ['nullable', 'regex:/^#[0-9a-fA-F]{6}$/'],
            'datetime', 'date' => ['nullable', 'date'],
            // "image" content-sniffs; mimes + size guard against SVG tricks.
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048', 'dimensions:max_width=4000,max_height=4000'],
            // Favicon: PNG or ICO, small.
            'file' => ['nullable', 'file', 'mimes:png,ico', 'max:512'],
            default => array_values(array_unique(array_merge(['nullable', 'string'], $rules))),
        };
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        $names = [];

        foreach (SettingsRegistry::fields($this->route('group')) as $key => $field) {
            $names[$key] = $field['label'];
        }

        return $names;
    }
}
