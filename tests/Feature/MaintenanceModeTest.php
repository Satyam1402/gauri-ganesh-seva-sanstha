<?php

namespace Tests\Feature;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Services\SettingsService;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MaintenanceModeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function enable(array $extra = []): void
    {
        $settings = app(SettingsService::class);
        $settings->set('maintenance.enabled', true);
        $settings->set('maintenance.heading', 'Back soon');
        $settings->set('maintenance.message', 'Upgrading the site.');

        foreach ($extra as $key => $value) {
            $settings->set($key, $value);
        }
    }

    public function test_site_is_online_by_default(): void
    {
        $this->get(route('home'))->assertOk();
        $this->get(route('faq.index'))->assertOk();
    }

    public function test_visitors_see_the_maintenance_page_when_enabled(): void
    {
        $this->enable(['maintenance.expected_back_at' => now()->addHours(2)->format('Y-m-d H:i:s')]);

        $response = $this->get(route('home'));

        $response->assertStatus(503);
        $response->assertHeader('Retry-After', '3600');
        $response->assertSee('Back soon')->assertSee('Upgrading the site.')->assertSee('Expected back');
        $response->assertSee('<meta name="robots" content="noindex">', false);
        // No debug details, no normal chrome.
        $response->assertDontSee('Whoops')->assertDontSee('aria-label="Main navigation"', false);

        $this->get(route('faq.index'))->assertStatus(503);
        $this->get(route('partners.index'))->assertStatus(503);
    }

    public function test_login_and_admin_panel_remain_reachable_during_maintenance(): void
    {
        $this->enable();

        $this->get(route('login'))->assertOk();
        $this->get('/up')->assertOk();

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->actingAs($admin)->get(route('admin.settings.edit', 'maintenance'))->assertOk();
        // Administrators can still preview the public site.
        $this->actingAs($admin)->get(route('home'))->assertOk();
    }

    public function test_signed_in_users_without_settings_permission_are_still_blocked(): void
    {
        $this->enable();

        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('home'))->assertStatus(503);
    }

    public function test_allowed_ips_bypass_maintenance(): void
    {
        $this->enable(['maintenance.allowed_ips' => "10.0.0.5\n127.0.0.1"]);

        $this->get(route('home'))->assertOk();

        $this->enable(['maintenance.allowed_ips' => '10.0.0.5']);

        $this->get(route('home'))->assertStatus(503);
    }

    public function test_admin_can_switch_maintenance_on_and_off_from_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $this->actingAs($admin)->put(route('admin.settings.update', 'maintenance'), [
            'enabled' => 1, 'heading' => 'Back soon', 'message' => 'Upgrading.',
        ])->assertRedirect(route('admin.settings.edit', 'maintenance'));

        // The admin stays in; an anonymous visitor is blocked.
        $this->actingAs($admin)->get(route('home'))->assertOk();
        auth()->logout();
        $this->get(route('home'))->assertStatus(503);

        $this->actingAs($admin)->put(route('admin.settings.update', 'maintenance'), [
            'enabled' => 0, 'heading' => 'Back soon',
        ]);

        auth()->logout();
        $this->get(route('home'))->assertOk();
    }
}
