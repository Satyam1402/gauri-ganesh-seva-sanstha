<?php

namespace Tests\Feature\Frontend;

use App\Models\HomeSection;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HomePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_renders_successfully(): void
    {
        $response = $this->get(route('home'));

        $response->assertOk();
    }

    public function test_homepage_shows_active_sections_in_order(): void
    {
        HomeSection::create([
            'key' => 'mission',
            'name' => 'Mission',
            'heading' => 'Our Mission Heading',
            'is_active' => true,
            'order_column' => 0,
        ]);

        HomeSection::create([
            'key' => 'vision',
            'name' => 'Vision',
            'heading' => 'Our Vision Heading',
            'is_active' => true,
            'order_column' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertSeeInOrder(['Our Mission Heading', 'Our Vision Heading']);
    }

    public function test_disabled_sections_are_not_shown_on_the_homepage(): void
    {
        HomeSection::create([
            'key' => 'mission',
            'name' => 'Mission',
            'heading' => 'Visible Mission Heading',
            'is_active' => true,
            'order_column' => 0,
        ]);

        HomeSection::create([
            'key' => 'vision',
            'name' => 'Vision',
            'heading' => 'Hidden Vision Heading',
            'is_active' => false,
            'order_column' => 1,
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('Visible Mission Heading');
        $response->assertDontSee('Hidden Vision Heading');
    }

    public function test_homepage_uses_page_seo_metadata(): void
    {
        $page = Page::create(['slug' => 'home', 'title' => 'Homepage']);
        $page->seo()->create([
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description.',
        ]);

        $response = $this->get(route('home'));

        $response->assertSee('Custom SEO Title');
        $response->assertSee('Custom SEO description.', false);
    }
}
