<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivityCategoryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_without_manage_activities_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.activity-categories.index'))->assertForbidden();
    }

    public function test_editor_can_create_a_category(): void
    {
        $editor = User::factory()->create();
        $editor->assignRole(RoleEnum::Editor->value);

        $response = $this->actingAs($editor)->post(route('admin.activity-categories.store'), [
            'name' => 'Medical Camps',
            'is_active' => 1,
        ]);

        $response->assertRedirect(route('admin.activity-categories.index'));

        $category = ActivityCategory::first();
        $this->assertSame('Medical Camps', $category->name);
        $this->assertSame('medical-camps', $category->slug);
    }

    public function test_a_category_with_activities_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $category = ActivityCategory::create(['name' => 'Tree Plantation', 'is_active' => true]);
        Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Riverside Plantation Drive',
            'short_description' => 'Planting saplings.',
            'full_description' => 'Full details here.',
            'activity_date' => now(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($admin)->delete(route('admin.activity-categories.destroy', $category));

        $response->assertRedirect();
        $this->assertNotNull($category->fresh());
    }

    public function test_categories_can_be_reordered(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $first = ActivityCategory::create(['name' => 'Blood Donation', 'order_column' => 0]);
        $second = ActivityCategory::create(['name' => 'Disaster Relief', 'order_column' => 1]);

        $response = $this->actingAs($admin)->postJson(route('admin.activity-categories.reorder'), [
            'order' => [$second->id, $first->id],
        ]);

        $response->assertOk();
        $this->assertSame(0, $second->fresh()->order_column);
        $this->assertSame(1, $first->fresh()->order_column);
    }
}
