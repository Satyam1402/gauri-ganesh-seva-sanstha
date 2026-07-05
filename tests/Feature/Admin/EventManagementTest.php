<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EventManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    private function category(): EventCategory
    {
        return EventCategory::create(['name' => 'Medical Camp', 'is_active' => true]);
    }

    private function event(EventCategory $category, array $overrides = []): Event
    {
        return Event::create(array_merge([
            'event_category_id' => $category->id,
            'title' => 'Free Health Checkup Camp',
            'short_description' => 'Free checkups for underserved families.',
            'full_description' => 'Detailed information about the camp.',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'draft',
        ], $overrides));
    }

    public function test_user_without_manage_events_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.events.index'))->assertForbidden();
    }

    public function test_content_manager_can_create_an_event_with_a_featured_image(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $category = $this->category();

        $response = $this->actingAs($contentManager)->post(route('admin.events.store'), [
            'event_category_id' => $category->id,
            'title' => 'Mega Health Camp 2026',
            'short_description' => 'Free checkups for everyone.',
            'full_description' => 'Full details about the camp.',
            'start_date' => now()->addDays(20)->toDateString(),
            'end_date' => now()->addDays(21)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '17:00',
            'venue' => 'Community Center',
            'address' => '123 Seva Marg',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'map_url' => 'https://maps.google.com/?q=community+center',
            'organizer' => 'GGSS Medical Wing',
            'max_participants' => 300,
            'requires_registration' => 1,
            'status' => 'published',
            'is_featured' => 1,
            'featured_image' => UploadedFile::fake()->image('camp.jpg'),
            'gallery' => [UploadedFile::fake()->image('gallery1.jpg')],
        ]);

        $event = Event::firstOrFail();
        $response->assertRedirect(route('admin.events.edit', $event));

        $this->assertSame('Mega Health Camp 2026', $event->title);
        $this->assertSame('mega-health-camp-2026', $event->slug);
        $this->assertTrue($event->is_featured);
        $this->assertTrue($event->requires_registration);
        $this->assertSame(300, $event->max_participants);
        $this->assertNotNull($event->getFirstMedia('featured_image'));
        $this->assertCount(1, $event->getMedia('gallery'));
    }

    public function test_end_date_cannot_be_before_start_date(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.events.store'), [
            'title' => 'Broken Event',
            'short_description' => 'Bad dates.',
            'full_description' => 'Details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'end_date' => now()->addDays(5)->toDateString(),
            'status' => 'draft',
            'featured_image' => UploadedFile::fake()->image('img.jpg'),
        ]);

        $response->assertSessionHasErrors('end_date');
        $this->assertSame(0, Event::count());
    }

    public function test_updating_an_event_saves_seo_fields(): void
    {
        $event = $this->event($this->category());

        $response = $this->actingAs($this->admin())->put(route('admin.events.update', $event), [
            'event_category_id' => $event->event_category_id,
            'title' => $event->title,
            'short_description' => $event->short_description,
            'full_description' => $event->full_description,
            'start_date' => $event->start_date->toDateString(),
            'status' => 'draft',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description.',
        ]);

        $response->assertRedirect(route('admin.events.edit', $event));
        $this->assertSame('Custom SEO Title', $event->fresh()->seo->meta_title);
    }

    public function test_publish_unpublish_and_cancel_toggle_the_status(): void
    {
        $admin = $this->admin();
        $event = $this->event($this->category());

        $this->actingAs($admin)->patch(route('admin.events.publish', $event))->assertRedirect();
        $this->assertSame('published', $event->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.events.unpublish', $event))->assertRedirect();
        $this->assertSame('draft', $event->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.events.cancel', $event))->assertRedirect();
        $this->assertSame('cancelled', $event->fresh()->status->value);
    }

    public function test_toggling_featured_flips_the_flag(): void
    {
        $event = $this->event($this->category());
        $this->assertFalse($event->fresh()->is_featured);

        $this->actingAs($this->admin())->patch(route('admin.events.feature', $event))->assertRedirect();
        $this->assertTrue($event->fresh()->is_featured);
    }

    public function test_deleting_an_event_soft_deletes_it_and_it_can_be_restored(): void
    {
        $admin = $this->admin();
        $event = $this->event($this->category());

        $this->actingAs($admin)->delete(route('admin.events.destroy', $event))->assertRedirect();
        $this->assertSoftDeleted($event);

        $this->actingAs($admin)->patch(route('admin.events.restore', $event))->assertRedirect();
        $this->assertNull($event->fresh()->deleted_at);
    }

    public function test_bulk_publish_updates_multiple_events(): void
    {
        $category = $this->category();
        $first = $this->event($category, ['title' => 'Event One']);
        $second = $this->event($category, ['title' => 'Event Two']);

        $this->actingAs($this->admin())->post(route('admin.events.bulk-publish'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSame('published', $first->fresh()->status->value);
        $this->assertSame('published', $second->fresh()->status->value);
    }

    public function test_bulk_delete_soft_deletes_multiple_events(): void
    {
        $category = $this->category();
        $first = $this->event($category, ['title' => 'Event One']);
        $second = $this->event($category, ['title' => 'Event Two']);

        $this->actingAs($this->admin())->post(route('admin.events.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_event_category_can_be_created_and_reordered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.event-categories.store'), [
            'name' => 'Tree Plantation',
            'is_active' => 1,
        ])->assertRedirect(route('admin.event-categories.index'));

        $first = EventCategory::where('slug', 'tree-plantation')->firstOrFail();
        $second = EventCategory::create(['name' => 'Medical Camp', 'is_active' => true]);

        $this->actingAs($admin)->postJson(route('admin.event-categories.reorder'), [
            'order' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertSame(0, $second->fresh()->order_column);
        $this->assertSame(1, $first->fresh()->order_column);
    }

    public function test_a_category_with_events_cannot_be_deleted(): void
    {
        $category = $this->category();
        $this->event($category);

        $this->actingAs($this->admin())
            ->from(route('admin.event-categories.index'))
            ->delete(route('admin.event-categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertNotNull(EventCategory::find($category->id));
    }
}
