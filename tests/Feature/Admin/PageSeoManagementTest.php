<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Page;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PageSeoManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_without_permission_cannot_view_seo_settings(): void
    {
        Page::create(['slug' => 'home', 'title' => 'Homepage']);

        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $response = $this->actingAs($viewer)->get(route('admin.pages.seo.edit', 'home'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_seo_settings(): void
    {
        $page = Page::create(['slug' => 'home', 'title' => 'Homepage']);

        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->put(route('admin.pages.seo.update', $page), [
            'meta_title' => 'Test Meta Title',
            'meta_description' => 'Test meta description.',
            'twitter_card' => 'summary',
        ]);

        $response->assertRedirect(route('admin.pages.seo.edit', $page));

        $this->assertSame('Test Meta Title', $page->seo->fresh()->meta_title);
        $this->assertSame('summary', $page->seo->fresh()->twitter_card);
    }
}
