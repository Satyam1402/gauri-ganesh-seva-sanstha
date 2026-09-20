<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\DonationCampaign;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Database\Seeders\HomeSectionsSeeder;
use Database\Seeders\PagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TestimonialsPageTest extends TestCase
{
    use RefreshDatabase;

    private function testimonial(array $overrides = []): Testimonial
    {
        return Testimonial::create(array_merge([
            'name' => 'Sunita Jadhav',
            'designation' => 'Mother of two',
            'location' => 'Pune',
            'content' => 'The medical camp gave my family care we could not have afforded otherwise.',
            'type' => 'beneficiary',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'consent_given' => true,
            'consented_at' => now()->subDays(2),
            'admin_notes' => 'PRIVATE-NOTE-MARKER',
        ], $overrides));
    }

    public function test_testimonials_page_lists_only_published_consented_entries(): void
    {
        $this->seed(PagesSeeder::class);

        $this->testimonial(['name' => 'Published Person']);
        $this->testimonial(['name' => 'Draft Person', 'status' => 'draft']);
        $this->testimonial(['name' => 'Unpublished Person', 'status' => 'unpublished']);
        $this->testimonial(['name' => 'Archived Person', 'status' => 'archived']);
        $this->testimonial(['name' => 'Pending Person', 'status' => 'pending_review']);
        $this->testimonial(['name' => 'No Consent Person', 'consent_given' => false, 'consented_at' => null]);
        $this->testimonial(['name' => 'Scheduled Person', 'published_at' => now()->addDays(3)]);
        $this->testimonial(['name' => 'Trashed Person'])->delete();

        $response = $this->get(route('testimonials.index'));

        $response->assertOk();
        $response->assertSee('Published Person');
        $response->assertDontSee('Draft Person');
        $response->assertDontSee('Unpublished Person');
        $response->assertDontSee('Archived Person');
        $response->assertDontSee('Pending Person');
        $response->assertDontSee('No Consent Person');
        $response->assertDontSee('Scheduled Person');
        $response->assertDontSee('Trashed Person');
    }

    public function test_admin_notes_are_never_rendered_publicly(): void
    {
        $this->testimonial(['is_featured' => true]);

        $this->get(route('testimonials.index'))->assertOk()->assertDontSee('PRIVATE-NOTE-MARKER');
        $this->get(route('home'))->assertOk()->assertDontSee('PRIVATE-NOTE-MARKER');

        $this->assertArrayNotHasKey('admin_notes', Testimonial::first()->toArray());
    }

    public function test_type_filter_narrows_the_listing_and_ignores_unknown_types(): void
    {
        $this->testimonial(['name' => 'Donor Dave', 'type' => 'donor']);
        $this->testimonial(['name' => 'Volunteer Vera', 'type' => 'volunteer']);

        $response = $this->get(route('testimonials.index', ['type' => 'donor']));
        $response->assertOk();
        $this->assertSame(['Donor Dave'], $response->viewData('testimonials')->pluck('name')->all());
        $this->assertSame('donor', $response->viewData('currentType')->value);

        $response = $this->get(route('testimonials.index', ['type' => 'not-a-type']));
        $response->assertOk();
        $this->assertNull($response->viewData('currentType'));
        $this->assertCount(2, $response->viewData('testimonials'));
    }

    public function test_featured_testimonials_appear_in_the_featured_strip_and_on_the_homepage(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->testimonial(['name' => 'Featured Fatima', 'is_featured' => true]);
        $this->testimonial(['name' => 'Regular Ravi', 'is_featured' => false]);

        $response = $this->get(route('testimonials.index'));
        $response->assertOk()->assertSee('Featured Stories');
        $this->assertSame(['Featured Fatima'], $response->viewData('featured')->pluck('name')->all());

        // Homepage section: featured entries only when any exist.
        $this->get(route('home'))->assertOk()->assertSee('Featured Fatima')->assertDontSee('Regular Ravi');
    }

    public function test_homepage_section_falls_back_to_latest_published_when_nothing_is_featured(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->testimonial(['name' => 'Regular Ravi', 'is_featured' => false]);

        $this->get(route('home'))->assertOk()->assertSee('Regular Ravi');
    }

    public function test_page_renders_seo_meta_and_structured_data(): void
    {
        $this->seed(PagesSeeder::class);
        $this->testimonial();

        $response = $this->get(route('testimonials.index'));

        $response->assertOk();
        $response->assertSee('<title>Testimonials — Gauri Ganesh Seva Sanstha</title>', false);
        $response->assertSee('<link rel="canonical" href="'.route('testimonials.index').'">', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('"@type":"CollectionPage"', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
        $response->assertDontSee('"@type":"Review"', false);
    }

    public function test_activity_page_shows_linked_testimonials_first(): void
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

        $this->testimonial(['name' => 'Linked Lata', 'testimonialable_type' => $activity->getMorphClass(), 'testimonialable_id' => $activity->id]);
        $this->testimonial(['name' => 'Generic Gopal', 'is_featured' => true]);

        $this->get(route('activities.show', $activity))
            ->assertOk()
            ->assertSee('Voices From This Work')
            ->assertSee('Linked Lata')
            ->assertDontSee('Generic Gopal');
    }

    public function test_campaign_page_falls_back_to_donor_testimonials_when_none_are_linked(): void
    {
        $campaign = DonationCampaign::create([
            'name' => 'Food Distribution',
            'short_description' => 'Meals for families facing food insecurity.',
            'full_description' => 'Full campaign description.',
            'goal_amount' => 500000,
            'status' => 'active',
        ]);

        $this->testimonial(['name' => 'Donor Dave', 'type' => 'donor']);
        $this->testimonial(['name' => 'Volunteer Vera', 'type' => 'volunteer']);

        $this->get(route('donations.campaigns.show', $campaign))
            ->assertOk()
            ->assertSee('Why People Give')
            ->assertSee('Donor Dave')
            ->assertDontSee('Volunteer Vera');
    }

    public function test_section_is_hidden_when_there_is_nothing_to_show(): void
    {
        $campaign = DonationCampaign::create([
            'name' => 'Quiet Campaign',
            'short_description' => 'No stories yet.',
            'full_description' => 'Full campaign description.',
            'goal_amount' => 1000,
            'status' => 'active',
        ]);

        $this->get(route('donations.campaigns.show', $campaign))
            ->assertOk()
            ->assertDontSee('Why People Give');
    }

    public function test_public_cache_is_invalidated_when_a_testimonial_changes(): void
    {
        $first = $this->testimonial(['name' => 'Original Olivia']);

        $this->get(route('testimonials.index'))->assertSee('Original Olivia');

        app(TestimonialService::class)->unpublish($first);

        $this->get(route('testimonials.index'))->assertDontSee('Original Olivia');
    }
}
