<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\MenuItem;
use App\Models\User;
use Database\Seeders\MenuItemsSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NavigationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    private function item(array $overrides = []): MenuItem
    {
        return MenuItem::create(array_merge([
            'location' => 'header',
            'label' => 'About',
            'link_type' => 'route',
            'route_name' => 'about',
            'is_active' => true,
            'order_column' => 0,
        ], $overrides));
    }

    public function test_seeded_default_navigation_renders_in_header_and_footer(): void
    {
        $this->seed(MenuItemsSeeder::class);

        $home = $this->get(route('home'));

        $home->assertOk();
        $home->assertSee('aria-label="Main navigation"', false);
        $home->assertSee('href="'.route('events.index').'"', false);
        $home->assertSee('aria-label="Explore"', false);
        $home->assertSee('aria-label="Get involved"', false);
        $home->assertSee('href="'.route('volunteer.create').'"', false);
    }

    public function test_admin_can_create_route_path_and_external_items(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.menu-items.store'), [
            'location' => 'header', 'label' => 'Programs', 'link_type' => 'route', 'route_name' => 'activities.index',
        ])->assertRedirect(route('admin.menu-items.index', ['location' => 'header']));

        $parent = MenuItem::where('label', 'Programs')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.menu-items.store'), [
            'location' => 'header', 'label' => 'Food Drives', 'link_type' => 'path', 'url' => '/activities?category=food', 'parent_id' => $parent->id,
        ])->assertSessionHasNoErrors();

        $this->actingAs($admin)->post(route('admin.menu-items.store'), [
            'location' => 'header', 'label' => 'Partner Site', 'link_type' => 'url', 'url' => 'https://example.org', 'open_in_new_tab' => 1, 'parent_id' => $parent->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame(2, $parent->children()->count());

        $home = $this->get(route('home'));
        $home->assertOk();
        $home->assertSee('aria-haspopup="true"', false);
        $home->assertSee('Food Drives');
        $home->assertSee('href="https://example.org"', false);
        $home->assertSee('target="_blank" rel="noopener noreferrer"', false);
        $home->assertSee('href="'.route('activities.index').'"', false);
    }

    public function test_unsafe_urls_and_non_public_routes_are_rejected(): void
    {
        $admin = $this->admin();

        $cases = [
            ['link_type' => 'url', 'url' => 'javascript:alert(1)'],
            ['link_type' => 'url', 'url' => 'ftp://example.org'],
            ['link_type' => 'path', 'url' => '//evil.com'],
            ['link_type' => 'path', 'url' => 'https://example.org'],
            ['link_type' => 'path', 'url' => 'campaigns'],
        ];

        foreach ($cases as $case) {
            $this->actingAs($admin)->post(route('admin.menu-items.store'), ['location' => 'header', 'label' => 'Bad'] + $case)
                ->assertSessionHasErrors('url');
        }

        $this->actingAs($admin)->post(route('admin.menu-items.store'), ['location' => 'header', 'label' => 'Bad', 'link_type' => 'route', 'route_name' => 'admin.dashboard'])
            ->assertSessionHasErrors('route_name');

        $this->actingAs($admin)->post(route('admin.menu-items.store'), ['location' => 'sidebar', 'label' => 'Bad', 'link_type' => 'route', 'route_name' => 'home'])
            ->assertSessionHasErrors('location');

        $this->assertSame(0, MenuItem::count());
    }

    public function test_admin_can_edit_toggle_reorder_and_delete_items(): void
    {
        $admin = $this->admin();
        $first = $this->item(['label' => 'First', 'order_column' => 0]);
        $second = $this->item(['label' => 'Second', 'order_column' => 1, 'route_name' => 'contact']);
        $child = $this->item(['label' => 'Child', 'parent_id' => $first->id]);

        $this->actingAs($admin)->put(route('admin.menu-items.update', $second), [
            'location' => 'header', 'label' => 'Second Renamed', 'link_type' => 'route', 'route_name' => 'faq.index', 'is_active' => 1,
        ])->assertRedirect(route('admin.menu-items.index', ['location' => 'header']));
        $this->assertSame('faq.index', $second->refresh()->route_name);

        $this->actingAs($admin)->patch(route('admin.menu-items.toggle', $second));
        $this->assertFalse($second->refresh()->is_active);
        $this->get(route('home'))->assertOk()->assertDontSee('Second Renamed');

        $this->actingAs($admin)->postJson(route('admin.menu-items.reorder'), ['location' => 'header', 'order' => [$second->id, $first->id]])->assertOk();
        $this->assertSame(0, $second->refresh()->order_column);
        $this->assertSame(1, $first->refresh()->order_column);

        $this->actingAs($admin)->delete(route('admin.menu-items.destroy', $first))
            ->assertRedirect(route('admin.menu-items.index', ['location' => 'header']));
        $this->assertDatabaseMissing('menu_items', ['id' => $first->id]);
        $this->assertDatabaseMissing('menu_items', ['id' => $child->id]);
    }

    public function test_menu_cache_is_invalidated_on_changes(): void
    {
        $item = $this->item(['label' => 'Cached Link']);
        $this->get(route('home'))->assertSee('Cached Link');

        $this->actingAs($this->admin())->put(route('admin.menu-items.update', $item), [
            'location' => 'header', 'label' => 'Updated Link', 'link_type' => 'route', 'route_name' => 'about', 'is_active' => 1,
        ]);

        $this->get(route('home'))->assertSee('Updated Link')->assertDontSee('Cached Link');
    }

    public function test_viewer_cannot_manage_navigation(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.menu-items.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.menu-items.store'), ['location' => 'header', 'label' => 'X', 'link_type' => 'route', 'route_name' => 'home'])->assertForbidden();
    }
}
