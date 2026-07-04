<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\HomeSection;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomeSectionManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function section(string $key = 'mission', int $order = 0): HomeSection
    {
        return HomeSection::create([
            'key' => $key,
            'name' => ucfirst($key),
            'heading' => 'Original Heading',
            'is_active' => true,
            'order_column' => $order,
        ]);
    }

    public function test_user_without_manage_homepage_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $response = $this->actingAs($viewer)->get(route('admin.home-sections.index'));

        $response->assertForbidden();
    }

    public function test_content_manager_can_view_and_update_a_section(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $section = $this->section('hero_banner');

        $this->actingAs($contentManager)
            ->get(route('admin.home-sections.index'))
            ->assertOk();

        $response = $this->actingAs($contentManager)->put(route('admin.home-sections.update', $section), [
            'heading' => 'Updated Heading',
            'subheading' => 'Updated Subheading',
            'buttons' => [
                ['label' => 'Donate Now', 'url' => '/donate', 'variant' => 'accent'],
            ],
        ]);

        $response->assertRedirect(route('admin.home-sections.edit', $section));

        $section->refresh();
        $this->assertSame('Updated Heading', $section->heading);
        $this->assertCount(1, $section->buttons);
        $this->assertSame('Donate Now', $section->buttons->first()->label);
    }

    public function test_toggling_a_section_flips_its_active_state(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $section = $this->section();
        $this->assertTrue($section->is_active);

        $this->actingAs($admin)->patch(route('admin.home-sections.toggle', $section))
            ->assertRedirect(route('admin.home-sections.index'));

        $this->assertFalse($section->fresh()->is_active);
    }

    public function test_sections_can_be_reordered(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $first = $this->section('mission', 0);
        $second = $this->section('vision', 1);

        $response = $this->actingAs($admin)->postJson(route('admin.home-sections.reorder'), [
            'order' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->order_column);
        $this->assertSame(1, $first->fresh()->order_column);
    }

    public function test_updating_a_section_removes_it_from_the_homepage_cache(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $section = $this->section();

        $this->get(route('home'))->assertSee('Original Heading');

        $this->actingAs($admin)->put(route('admin.home-sections.update', $section), [
            'heading' => 'Freshly Updated Heading',
        ]);

        $this->get(route('home'))->assertSee('Freshly Updated Heading');
    }
}
