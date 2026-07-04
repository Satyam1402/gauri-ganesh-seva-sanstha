<?php

namespace Tests\Feature\Frontend;

use App\Models\AboutSection;
use App\Models\OrgProfile;
use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AboutPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_about_page_renders_successfully(): void
    {
        $response = $this->get(route('about'));

        $response->assertOk();
    }

    public function test_about_page_shows_active_sections_in_order(): void
    {
        AboutSection::create([
            'key' => 'mission',
            'name' => 'Mission',
            'heading' => 'Mission Heading',
            'is_active' => true,
            'order_column' => 0,
        ]);

        AboutSection::create([
            'key' => 'vision',
            'name' => 'Vision',
            'heading' => 'Vision Heading',
            'is_active' => true,
            'order_column' => 1,
        ]);

        $response = $this->get(route('about'));

        $response->assertSeeInOrder(['Mission Heading', 'Vision Heading']);
    }

    public function test_disabled_sections_are_not_shown(): void
    {
        AboutSection::create([
            'key' => 'mission',
            'name' => 'Mission',
            'heading' => 'Visible Heading',
            'is_active' => true,
            'order_column' => 0,
        ]);

        AboutSection::create([
            'key' => 'awards_recognition',
            'name' => 'Awards & Recognition',
            'heading' => 'Hidden Awards Heading',
            'is_active' => false,
            'order_column' => 1,
        ]);

        $response = $this->get(route('about'));

        $response->assertSee('Visible Heading');
        $response->assertDontSee('Hidden Awards Heading');
    }

    public function test_registration_legal_section_shows_org_profile_data(): void
    {
        AboutSection::create([
            'key' => 'registration_legal',
            'name' => 'Registration & Legal Information',
            'heading' => 'Registration & Legal Information',
            'is_active' => true,
            'order_column' => 0,
        ]);

        OrgProfile::create([
            'legal_name' => 'Test Trust Name',
            'registration_no' => 'TEST/REG/123',
        ]);

        $response = $this->get(route('about'));

        $response->assertSee('Test Trust Name');
        $response->assertSee('TEST/REG/123');
    }

    public function test_about_page_uses_page_seo_metadata(): void
    {
        $page = Page::create(['slug' => 'about', 'title' => 'About Us']);
        $page->seo()->create([
            'meta_title' => 'Custom About SEO Title',
            'meta_description' => 'Custom about description.',
        ]);

        $response = $this->get(route('about'));

        $response->assertSee('Custom About SEO Title');
        $response->assertSee('Custom about description.', false);
    }
}
