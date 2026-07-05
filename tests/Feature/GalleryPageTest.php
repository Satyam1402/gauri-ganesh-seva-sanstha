<?php

namespace Tests\Feature;

use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GalleryPageTest extends TestCase
{
    use RefreshDatabase;

    private function category(): GalleryCategory
    {
        return GalleryCategory::create(['name' => 'Events', 'is_active' => true]);
    }

    public function test_gallery_index_only_lists_published_albums(): void
    {
        $category = $this->category();

        GalleryAlbum::create([
            'gallery_category_id' => $category->id,
            'title' => 'Published Album',
            'status' => 'published',
        ]);

        GalleryAlbum::create([
            'gallery_category_id' => $category->id,
            'title' => 'Draft Album',
            'status' => 'draft',
        ]);

        $response = $this->get(route('gallery.index'));

        $response->assertOk();
        $response->assertSee('Published Album');
        $response->assertDontSee('Draft Album');
    }

    public function test_gallery_index_can_be_filtered_by_category(): void
    {
        $events = $this->category();
        $camps = GalleryCategory::create(['name' => 'Medical Camps', 'is_active' => true]);

        GalleryAlbum::create([
            'gallery_category_id' => $events->id,
            'title' => 'Festival Album',
            'status' => 'published',
        ]);

        GalleryAlbum::create([
            'gallery_category_id' => $camps->id,
            'title' => 'Camp Album',
            'status' => 'published',
        ]);

        $response = $this->get(route('gallery.index', ['category' => $camps->slug]));

        $response->assertOk();
        $response->assertSee('Camp Album');
        $response->assertDontSee('Festival Album');
    }

    public function test_a_draft_album_detail_page_returns_404(): void
    {
        $album = GalleryAlbum::create([
            'gallery_category_id' => $this->category()->id,
            'title' => 'Draft Album',
            'status' => 'draft',
        ]);

        $this->get(route('gallery.show', $album))->assertNotFound();
    }

    public function test_a_published_album_detail_page_shows_only_active_photos(): void
    {
        $album = GalleryAlbum::create([
            'gallery_category_id' => $this->category()->id,
            'title' => 'Published Album',
            'status' => 'published',
        ]);

        $album->photos()->create([
            'caption' => 'Visible community moment',
            'is_active' => true,
            'order_column' => 1,
        ]);

        $album->photos()->create([
            'caption' => 'Hidden outtake photo',
            'is_active' => false,
            'order_column' => 2,
        ]);

        $response = $this->get(route('gallery.show', $album));

        $response->assertOk();
        $response->assertSee('Published Album');
        $response->assertSee('Visible community moment');
        $response->assertDontSee('Hidden outtake photo');
    }

    public function test_a_published_album_renders_its_active_videos(): void
    {
        $album = GalleryAlbum::create([
            'gallery_category_id' => $this->category()->id,
            'title' => 'Published Album',
            'status' => 'published',
        ]);

        $album->videos()->create([
            'title' => 'Event Highlights Reel',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'video_id' => 'dQw4w9WgXcQ',
            'is_active' => true,
            'order_column' => 1,
        ]);

        $album->videos()->create([
            'title' => 'Unlisted Raw Footage',
            'provider' => 'youtube',
            'video_url' => 'https://youtu.be/xxxxxxxxxxx',
            'video_id' => 'xxxxxxxxxxx',
            'is_active' => false,
            'order_column' => 2,
        ]);

        $response = $this->get(route('gallery.show', $album));

        $response->assertOk();
        $response->assertSee('Event Highlights Reel');
        $response->assertSee('youtube-nocookie.com/embed/dQw4w9WgXcQ', false);
        $response->assertDontSee('Unlisted Raw Footage');
    }
}
