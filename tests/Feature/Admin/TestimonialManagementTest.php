<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\Testimonial;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class TestimonialManagementTest extends TestCase
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

    private function testimonial(array $overrides = []): Testimonial
    {
        return Testimonial::create(array_merge([
            'name' => 'Sunita Jadhav',
            'designation' => 'Mother of two',
            'location' => 'Pune',
            'content' => 'The medical camp gave my family care we could not have afforded otherwise.',
            'type' => 'beneficiary',
            'status' => 'draft',
            'consent_given' => true,
            'consented_at' => now()->subDay(),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Anita Sharma',
            'designation' => 'Monthly Donor',
            'organization' => 'Sharma & Co.',
            'location' => 'Mumbai',
            'content' => 'Seeing the impact update after my donation made all the difference to me.',
            'rating' => 5,
            'type' => 'donor',
            'status' => 'published',
            'is_featured' => 1,
            'display_order' => 2,
            'consent_given' => 1,
            'consented_at' => now()->subDays(2)->toDateString(),
            'admin_notes' => 'Consent received by email.',
        ], $overrides);
    }

    public function test_user_without_manage_testimonials_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $testimonial = $this->testimonial();

        $this->actingAs($viewer)->get(route('admin.testimonials.index'))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.testimonials.create'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.testimonials.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($viewer)->patch(route('admin.testimonials.publish', $testimonial))->assertForbidden();
        $this->actingAs($viewer)->delete(route('admin.testimonials.destroy', $testimonial))->assertForbidden();

        $this->assertSame(0, Testimonial::where('name', 'Anita Sharma')->count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.testimonials.index'))->assertRedirect(route('login'));
    }

    public function test_content_manager_can_create_a_testimonial_with_a_profile_photo(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $response = $this->actingAs($contentManager)->post(route('admin.testimonials.store'), $this->validPayload([
            'profile_photo' => UploadedFile::fake()->image('anita.jpg', 400, 400),
        ]));

        $testimonial = Testimonial::firstOrFail();
        $response->assertRedirect(route('admin.testimonials.edit', $testimonial));

        $this->assertSame('Anita Sharma', $testimonial->name);
        $this->assertSame('donor', $testimonial->type->value);
        $this->assertSame('published', $testimonial->status->value);
        $this->assertTrue($testimonial->is_featured);
        $this->assertTrue($testimonial->consent_given);
        $this->assertNotNull($testimonial->consented_at);
        $this->assertNotNull($testimonial->published_at);
        $this->assertSame(5, $testimonial->rating);
        $this->assertSame($contentManager->id, $testimonial->created_by);
        $this->assertNotNull($testimonial->getFirstMedia('profile_photo'));
    }

    public function test_validation_rejects_missing_and_invalid_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.testimonials.store'), [
            'name' => '',
            'content' => 'Too short',
            'type' => 'alien',
            'status' => 'live',
            'rating' => 9,
            'related' => 'campaign:abc',
            'consented_at' => now()->addDays(3)->toDateString(),
            'profile_photo' => UploadedFile::fake()->create('notes.pdf', 100, 'application/pdf'),
        ]);

        $response->assertSessionHasErrors(['name', 'content', 'type', 'status', 'rating', 'related', 'consented_at', 'profile_photo']);
        $this->assertSame(0, Testimonial::count());
    }

    public function test_related_activity_must_exist(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.testimonials.store'), $this->validPayload([
            'related' => 'activity:999',
        ]));

        $response->assertSessionHasErrors('related');
        $this->assertSame(0, Testimonial::count());
    }

    public function test_testimonial_can_be_linked_to_an_activity(): void
    {
        $category = ActivityCategory::create(['name' => 'Food', 'is_active' => true]);
        $activity = Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Community Meal Drive',
            'short_description' => 'Weekly meals.',
            'full_description' => 'Details.',
            'activity_date' => now()->toDateString(),
            'status' => 'published',
        ]);

        $this->actingAs($this->admin())->post(route('admin.testimonials.store'), $this->validPayload([
            'related' => "activity:{$activity->id}",
        ]));

        $testimonial = Testimonial::firstOrFail();
        $this->assertTrue($testimonial->testimonialable->is($activity));
        $this->assertSame('Activity: Community Meal Drive', $testimonial->relatedLabel());
    }

    public function test_saving_as_published_without_consent_downgrades_to_pending_review(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.testimonials.store'), $this->validPayload([
            'consent_given' => 0,
            'consented_at' => null,
        ]));

        $testimonial = Testimonial::firstOrFail();
        $response->assertSessionHas('status', fn (string $message) => str_contains($message, 'Pending Review'));

        $this->assertSame('pending_review', $testimonial->status->value);
        $this->assertFalse($testimonial->consent_given);
        $this->assertNull($testimonial->consented_at);
    }

    public function test_admin_can_edit_a_testimonial_and_replace_or_remove_its_photo(): void
    {
        $testimonial = $this->testimonial();
        $testimonial->addMedia(UploadedFile::fake()->image('old.jpg', 200, 200))->toMediaCollection('profile_photo');

        $response = $this->actingAs($this->admin())->put(route('admin.testimonials.update', $testimonial), $this->validPayload([
            'name' => 'Sunita J.',
            'status' => 'unpublished',
            'rating' => '',
            'profile_photo' => UploadedFile::fake()->image('new.jpg', 200, 200),
        ]));

        $response->assertRedirect(route('admin.testimonials.edit', $testimonial));

        $testimonial->refresh();
        $this->assertSame('Sunita J.', $testimonial->name);
        $this->assertSame('unpublished', $testimonial->status->value);
        $this->assertNull($testimonial->rating);
        $this->assertSame('new.jpg', $testimonial->getFirstMedia('profile_photo')->file_name);
        $this->assertCount(1, $testimonial->getMedia('profile_photo'));

        $this->actingAs($this->admin())->put(route('admin.testimonials.update', $testimonial), $this->validPayload([
            'remove_profile_photo' => 1,
        ]));

        $this->assertNull($testimonial->refresh()->getFirstMedia('profile_photo'));
    }

    public function test_publish_sets_published_at_and_unpublish_reverts(): void
    {
        $testimonial = $this->testimonial();

        $this->actingAs($this->admin())->patch(route('admin.testimonials.publish', $testimonial))
            ->assertSessionHas('status', 'Testimonial published.');

        $testimonial->refresh();
        $this->assertSame('published', $testimonial->status->value);
        $this->assertNotNull($testimonial->published_at);

        $this->actingAs($this->admin())->patch(route('admin.testimonials.unpublish', $testimonial))
            ->assertSessionHas('status', 'Testimonial unpublished.');

        $this->assertSame('unpublished', $testimonial->refresh()->status->value);
    }

    public function test_publish_is_refused_without_consent(): void
    {
        $testimonial = $this->testimonial(['consent_given' => false, 'consented_at' => null]);

        $this->actingAs($this->admin())->patch(route('admin.testimonials.publish', $testimonial))
            ->assertSessionHas('error');

        $this->assertSame('draft', $testimonial->refresh()->status->value);
    }

    public function test_admin_can_archive_feature_and_reorder(): void
    {
        $testimonial = $this->testimonial(['status' => 'published', 'published_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.testimonials.feature', $testimonial));
        $this->assertTrue($testimonial->refresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.testimonials.order', $testimonial), ['display_order' => 7]);
        $this->assertSame(7, $testimonial->refresh()->display_order);

        $this->actingAs($admin)->patch(route('admin.testimonials.archive', $testimonial));
        $this->assertSame('archived', $testimonial->refresh()->status->value);
    }

    public function test_admin_can_soft_delete_and_restore(): void
    {
        $testimonial = $this->testimonial();
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.testimonials.destroy', $testimonial))
            ->assertRedirect(route('admin.testimonials.index'));

        $this->assertSoftDeleted('testimonials', ['id' => $testimonial->id]);

        $this->actingAs($admin)->get(route('admin.testimonials.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Sunita Jadhav');

        $this->actingAs($admin)->patch(route('admin.testimonials.restore', $testimonial))
            ->assertRedirect(route('admin.testimonials.index', ['trashed' => 1]));

        $this->assertNull($testimonial->fresh()->deleted_at);
    }

    public function test_bulk_publish_skips_entries_without_consent(): void
    {
        $consented = $this->testimonial(['name' => 'Consented Person']);
        $unconsented = $this->testimonial(['name' => 'Unconsented Person', 'consent_given' => false, 'consented_at' => null]);

        $response = $this->actingAs($this->admin())->post(route('admin.testimonials.bulk-status'), [
            'action' => 'publish',
            'ids' => [$consented->id, $unconsented->id],
        ]);

        $response->assertSessionHas('status', '1 testimonials published. 1 skipped — consent has not been recorded.');
        $this->assertSame('published', $consented->refresh()->status->value);
        $this->assertSame('draft', $unconsented->refresh()->status->value);
    }

    public function test_bulk_archive_and_bulk_delete(): void
    {
        $first = $this->testimonial(['name' => 'First']);
        $second = $this->testimonial(['name' => 'Second']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.testimonials.bulk-status'), [
            'action' => 'archive',
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 testimonials archived.');

        $this->assertSame('archived', $first->refresh()->status->value);

        $this->actingAs($admin)->post(route('admin.testimonials.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 testimonials moved to trash.');

        $this->assertSoftDeleted('testimonials', ['id' => $first->id]);
        $this->assertSoftDeleted('testimonials', ['id' => $second->id]);
    }

    public function test_index_search_and_filters_narrow_the_listing(): void
    {
        $this->testimonial(['name' => 'Donor Dave', 'type' => 'donor', 'status' => 'published', 'published_at' => now()]);
        $this->testimonial(['name' => 'Volunteer Vera', 'type' => 'volunteer', 'status' => 'draft']);

        $response = $this->actingAs($this->admin())->get(route('admin.testimonials.index', ['type' => 'donor', 'status' => 'published']));

        $response->assertOk();
        $names = $response->viewData('testimonials')->pluck('name')->all();
        $this->assertSame(['Donor Dave'], $names);

        $response = $this->actingAs($this->admin())->get(route('admin.testimonials.index', ['q' => 'Vera']));
        $this->assertSame(['Volunteer Vera'], $response->viewData('testimonials')->pluck('name')->all());
    }

    public function test_show_page_displays_private_notes_to_admins(): void
    {
        $testimonial = $this->testimonial(['admin_notes' => 'Consent form on file in the office.']);

        $this->actingAs($this->admin())->get(route('admin.testimonials.show', $testimonial))
            ->assertOk()
            ->assertSee('Consent form on file in the office.');
    }
}
