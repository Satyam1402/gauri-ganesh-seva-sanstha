<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\GalleryPhoto;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class GalleryManagementTest extends TestCase
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

    private function category(): GalleryCategory
    {
        return GalleryCategory::create(['name' => 'Events', 'is_active' => true]);
    }

    private function album(GalleryCategory $category, array $overrides = []): GalleryAlbum
    {
        return GalleryAlbum::create(array_merge([
            'gallery_category_id' => $category->id,
            'title' => 'Ganesh Chaturthi 2025',
            'status' => 'draft',
        ], $overrides));
    }

    public function test_user_without_manage_gallery_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.gallery-albums.index'))->assertForbidden();
    }

    public function test_content_manager_can_create_an_album_with_a_cover_image(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $category = $this->category();

        $response = $this->actingAs($contentManager)->post(route('admin.gallery-albums.store'), [
            'gallery_category_id' => $category->id,
            'title' => 'Ganesh Chaturthi 2025',
            'description' => 'Highlights from the celebration.',
            'event_date' => now()->toDateString(),
            'location' => 'Pune',
            'status' => 'published',
            'is_featured' => 1,
            'cover_image' => UploadedFile::fake()->image('cover.jpg'),
        ]);

        $album = GalleryAlbum::firstOrFail();
        $response->assertRedirect(route('admin.gallery-albums.edit', $album));

        $this->assertSame('Ganesh Chaturthi 2025', $album->title);
        $this->assertTrue($album->is_featured);
        $this->assertNotNull($album->getFirstMedia('cover_image'));
    }

    public function test_updating_an_album_saves_seo_fields(): void
    {
        $album = $this->album($this->category());

        $response = $this->actingAs($this->admin())->put(route('admin.gallery-albums.update', $album), [
            'gallery_category_id' => $album->gallery_category_id,
            'title' => $album->title,
            'status' => 'draft',
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description.',
        ]);

        $response->assertRedirect(route('admin.gallery-albums.edit', $album));
        $this->assertSame('Custom SEO Title', $album->fresh()->seo->meta_title);
    }

    public function test_publish_and_unpublish_toggle_the_status(): void
    {
        $admin = $this->admin();
        $album = $this->album($this->category());

        $this->actingAs($admin)->patch(route('admin.gallery-albums.publish', $album))->assertRedirect();
        $this->assertSame('published', $album->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.gallery-albums.unpublish', $album))->assertRedirect();
        $this->assertSame('draft', $album->fresh()->status->value);
    }

    public function test_toggling_featured_flips_the_flag(): void
    {
        $album = $this->album($this->category());
        $this->assertFalse($album->fresh()->is_featured);

        $this->actingAs($this->admin())->patch(route('admin.gallery-albums.feature', $album))->assertRedirect();
        $this->assertTrue($album->fresh()->is_featured);
    }

    public function test_deleting_an_album_soft_deletes_it_and_it_can_be_restored(): void
    {
        $admin = $this->admin();
        $album = $this->album($this->category());

        $this->actingAs($admin)->delete(route('admin.gallery-albums.destroy', $album))->assertRedirect();
        $this->assertSoftDeleted($album);

        $this->actingAs($admin)->patch(route('admin.gallery-albums.restore', $album))->assertRedirect();
        $this->assertNull($album->fresh()->deleted_at);
    }

    public function test_photos_can_be_bulk_uploaded_to_an_album(): void
    {
        $album = $this->album($this->category());

        $response = $this->actingAs($this->admin())->post(route('admin.gallery-photos.store', $album), [
            'photos' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.jpg'),
            ],
            'photographer' => 'GGSS Volunteer',
        ]);

        $response->assertRedirect(route('admin.gallery-albums.edit', $album));

        $this->assertSame(2, $album->photos()->count());
        $this->assertSame([1, 2], $album->photos()->pluck('order_column')->all());
        $this->assertSame('GGSS Volunteer', $album->photos()->first()->photographer);
        $this->assertNotNull($album->photos()->first()->getFirstMedia('image'));
    }

    public function test_the_dropzone_upload_receives_a_json_response(): void
    {
        $album = $this->album($this->category());

        $response = $this->actingAs($this->admin())->postJson(route('admin.gallery-photos.store', $album), [
            'photos' => [UploadedFile::fake()->image('one.jpg')],
        ]);

        $response->assertOk()->assertJson(['status' => 'ok', 'uploaded' => 1]);
    }

    public function test_photos_can_be_reordered(): void
    {
        $album = $this->album($this->category());
        $first = $album->photos()->create(['is_active' => true, 'order_column' => 1]);
        $second = $album->photos()->create(['is_active' => true, 'order_column' => 2]);

        $this->actingAs($this->admin())->postJson(route('admin.gallery-photos.reorder', $album), [
            'order' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertSame(1, $second->fresh()->order_column);
        $this->assertSame(2, $first->fresh()->order_column);
    }

    public function test_photos_can_be_bulk_deleted(): void
    {
        $album = $this->album($this->category());
        $first = $album->photos()->create(['is_active' => true, 'order_column' => 1]);
        $second = $album->photos()->create(['is_active' => true, 'order_column' => 2]);

        $this->actingAs($this->admin())->post(route('admin.gallery-photos.bulk-delete', $album), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSame(0, $album->photos()->count());
    }

    public function test_a_photo_from_another_album_cannot_be_modified_through_the_wrong_album(): void
    {
        $category = $this->category();
        $album = $this->album($category);
        $otherAlbum = $this->album($category, ['title' => 'Another Album']);
        $photo = $otherAlbum->photos()->create(['is_active' => true, 'order_column' => 1]);

        $this->actingAs($this->admin())
            ->delete(route('admin.gallery-photos.destroy', [$album, $photo]))
            ->assertNotFound();

        $this->assertNotNull(GalleryPhoto::find($photo->id));
    }

    public function test_a_youtube_video_can_be_added_and_its_id_is_extracted(): void
    {
        $album = $this->album($this->category());

        $response = $this->actingAs($this->admin())->post(route('admin.gallery-videos.store', $album), [
            'title' => 'Event Highlights',
            'provider' => 'youtube',
            'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
        ]);

        $response->assertRedirect(route('admin.gallery-albums.edit', $album));

        $video = $album->videos()->firstOrFail();
        $this->assertSame('dQw4w9WgXcQ', $video->video_id);
        $this->assertSame('https://www.youtube-nocookie.com/embed/dQw4w9WgXcQ', $video->embedUrl());
    }

    public function test_an_unrecognisable_video_url_is_rejected(): void
    {
        $album = $this->album($this->category());

        $response = $this->actingAs($this->admin())->post(route('admin.gallery-videos.store', $album), [
            'title' => 'Broken Video',
            'provider' => 'youtube',
            'video_url' => 'https://example.com/not-a-video',
        ]);

        $response->assertSessionHasErrors('video_url');
        $this->assertSame(0, $album->videos()->count());
    }

    public function test_a_video_can_be_deleted(): void
    {
        $album = $this->album($this->category());
        $video = $album->videos()->create([
            'title' => 'Event Highlights',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'video_id' => 'dQw4w9WgXcQ',
            'is_active' => true,
            'order_column' => 1,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.gallery-videos.destroy', [$album, $video]))
            ->assertRedirect();

        $this->assertSame(0, $album->videos()->count());
    }
}
