<?php

namespace App\Http\Requests\Admin;

use App\Support\Reports\DateRange;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Validates report filters. Every value is either a known preset/enum
 * value or an integer id, so nothing user-typed reaches the queries
 * unchecked (and nothing sensitive ever appears in the URL).
 */
class ReportFilterRequest extends FormRequest
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
            'period' => ['nullable', 'string', Rule::in(array_keys(DateRange::PRESETS))],
            'from' => ['nullable', 'date', 'required_if:period,custom'],
            'to' => ['nullable', 'date', 'required_if:period,custom'],
            'campaign' => ['nullable', 'integer', 'exists:donation_campaigns,id'],
            'category' => ['nullable', 'string', 'max:50'],
            'event' => ['nullable', 'integer', 'exists:events,id'],
            'status' => ['nullable', 'string', 'max:30', 'alpha_dash'],
            'method' => ['nullable', 'string', 'max:30', 'alpha_dash'],
            'state' => ['nullable', 'string', 'max:100'],
            'fresh' => ['nullable', 'boolean'],
        ];
    }

    public function range(string $default = 'this_month'): DateRange
    {
        return DateRange::fromRequest($this->validated(), $default);
    }

    /**
     * Report-specific filters, validated, without the date keys.
     *
     * @return array<string, mixed>
     */
    public function filters(): array
    {
        return array_filter(
            collect($this->validated())->except(['period', 'from', 'to', 'fresh'])->all(),
            fn ($value) => $value !== null && $value !== '',
        );
    }
}
