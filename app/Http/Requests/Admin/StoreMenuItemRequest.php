<?php

namespace App\Http\Requests\Admin;

use App\Enums\MenuLinkType;
use App\Enums\MenuLocation;
use App\Models\MenuItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreMenuItemRequest extends FormRequest
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
            'location' => ['required', 'string', Rule::in(MenuLocation::values())],
            'parent_id' => ['nullable', 'integer', 'exists:menu_items,id'],
            'label' => ['required', 'string', 'max:100'],
            'link_type' => ['required', 'string', Rule::in(MenuLinkType::values())],
            // Only whitelisted public routes — never admin/auth/callbacks.
            'route_name' => ['required_if:link_type,route', 'nullable', 'string', Rule::in(array_keys(MenuItem::LINKABLE_ROUTES))],
            'url' => array_merge(['required_unless:link_type,route', 'nullable', 'string', 'max:255'], $this->urlRules()),
            'open_in_new_tab' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Internal paths must be site-relative ("/campaigns", no "//host");
     * external links must be http(s). Anything else (javascript:, data:)
     * is rejected by both branches.
     *
     * @return list<mixed>
     */
    protected function urlRules(): array
    {
        return match ($this->input('link_type')) {
            MenuLinkType::Path->value => ['regex:#^/(?!/)[^\s]*$#'],
            MenuLinkType::External->value => ['url:http,https'],
            default => [],
        };
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.regex' => 'Internal paths must start with a single “/”, e.g. /campaigns.',
            'url.url' => 'External links must be a full http:// or https:// address.',
            'route_name.in' => 'Choose one of the listed site pages.',
        ];
    }
}
