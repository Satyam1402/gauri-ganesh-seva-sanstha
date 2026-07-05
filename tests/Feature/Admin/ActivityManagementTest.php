<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ActivityManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function category(): ActivityCategory
    {
        return ActivityCategory::create(['name' => 'Medical Camps', 'is_active' => true]);
    }

    private function activity(ActivityCategory $category, array $overrides = []): Activity
    {
        return Activity::create(array_merge([
            'activity_category_id' => $category->id,
            'title' => 'Free Health Checkup Camp',
            'short_description' => 'Free checkups for underserved families.',
            'full_description' => 'Detailed information about the camp.',
            'activity_date' => now(),
            'status' => 'draft',
        ], $overrides));
    }

    public function test_user_without_manage_activities_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.activities.index'))->assertForbidden();
    }

    public function test_content_manager_can_create_an_activity_with_a_featured_image(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $category = $this->category();

        $response = $this->actingAs($contentManager)->post(route('admin.activities.store'), [
            'activity_category_id' => $category->id,
            'title' => 'Free Health Checkup Camp',
            'short_description' => 'Free checkups for underserved families.',
            'full_description' => 'Detailed information about the camp.',
            'activity_date' => now()->toDateString(),
            'status' => 'published',
            'is_featured' => 1,
            'featured_image' => UploadedFile::fake()->image('camp.jpg'),
        ]);

        $activity = Activity::firstOrFail();
        $response->assertRedirect(route('admin.activities.edit', $activity));

        $this->assertSame('Free Health Checkup Camp', $activity->title);
        $this->assertTrue($activity->is_featured);
        $this->assertNotNull($activity->getFirstMedia('featured_image'));
    }

    public function test_updating_an_activity_saves_seo_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $category = $this->category();
        $activity = $this->activity($category);

        $response = $this->actingAs($admin)->put(route('admin.activities.update', $activity), [
            'activity_category_id' => $category->id,
            'title' => $activity->title,
            'short_description' => $activity->short_description,
            'full_description' => $activity->full_description,
            'activity_date' => $activity->activity_date->toDateString(),
            'status' => 'draft',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description.',
        ]);

        $response->assertRedirect(route('admin.activities.edit', $activity));
        $this->assertSame('Custom SEO Title', $activity->fresh()->seo->meta_title);
    }

    public function test_publish_and_unpublish_toggle_the_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $activity = $this->activity($this->category());

        $this->actingAs($admin)->patch(route('admin.activities.publish', $activity))->assertRedirect();
        $this->assertSame('published', $activity->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.activities.unpublish', $activity))->assertRedirect();
        $this->assertSame('draft', $activity->fresh()->status->value);
    }

    public function test_toggling_featured_flips_the_flag(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $activity = $this->activity($this->category());
        $this->assertFalse($activity->fresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.activities.feature', $activity))->assertRedirect();
        $this->assertTrue($activity->fresh()->is_featured);
    }

    public function test_deleting_an_activity_soft_deletes_it_and_it_can_be_restored(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $activity = $this->activity($this->category());

        $this->actingAs($admin)->delete(route('admin.activities.destroy', $activity))->assertRedirect();
        $this->assertSoftDeleted($activity);

        $this->actingAs($admin)->patch(route('admin.activities.restore', $activity))->assertRedirect();
        $this->assertNull($activity->fresh()->deleted_at);
    }

    public function test_bulk_publish_updates_multiple_activities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $category = $this->category();
        $first = $this->activity($category, ['title' => 'Activity One']);
        $second = $this->activity($category, ['title' => 'Activity Two']);

        $this->actingAs($admin)->post(route('admin.activities.bulk-publish'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSame('published', $first->fresh()->status->value);
        $this->assertSame('published', $second->fresh()->status->value);
    }

    public function test_bulk_category_update_reassigns_multiple_activities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $category = $this->category();
        $newCategory = ActivityCategory::create(['name' => 'Education Support', 'is_active' => true]);
        $activity = $this->activity($category);

        $this->actingAs($admin)->post(route('admin.activities.bulk-category'), [
            'ids' => [$activity->id],
            'activity_category_id' => $newCategory->id,
        ])->assertRedirect();

        $this->assertSame($newCategory->id, $activity->fresh()->activity_category_id);
    }

    public function test_bulk_delete_soft_deletes_multiple_activities(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $category = $this->category();
        $first = $this->activity($category, ['title' => 'Activity One']);
        $second = $this->activity($category, ['title' => 'Activity Two']);

        $this->actingAs($admin)->post(route('admin.activities.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }
}
