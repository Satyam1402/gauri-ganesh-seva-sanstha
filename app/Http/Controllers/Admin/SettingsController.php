<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSettingsRequest;
use App\Services\SettingsService;
use App\Support\SettingsRegistry;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function index(): RedirectResponse
    {
        return redirect()->route('admin.settings.edit', 'general');
    }

    public function edit(string $group): View
    {
        Gate::authorize('manage settings');

        abort_unless(SettingsRegistry::hasGroup($group), 404);

        $definition = SettingsRegistry::groups()[$group];

        return view('admin.settings.edit', [
            'group' => $group,
            'definition' => $definition,
            'tabs' => SettingsRegistry::tabs(),
            'values' => $this->settings->group($group),
            'timezones' => timezone_identifiers_list(),
            'envSecrets' => $group === 'integrations' ? $this->settings->envSecretStatus() : [],
            'maintenanceOn' => (bool) $this->settings->get('maintenance.enabled', false),
        ]);
    }

    public function update(UpdateSettingsRequest $request, string $group): RedirectResponse
    {
        Gate::authorize('manage settings');

        abort_unless(SettingsRegistry::hasGroup($group), 404);

        $this->settings->updateGroup($group, $request->validated());

        return redirect()->route('admin.settings.edit', $group)
            ->with('status', SettingsRegistry::groups()[$group]['label'].' settings saved.');
    }
}
