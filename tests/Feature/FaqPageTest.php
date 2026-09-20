<?php

namespace Tests\Feature;

use App\Models\Faq;
use App\Models\FaqCategory;
use App\Services\FaqService;
use Database\Seeders\HomeSectionsSeeder;
use Database\Seeders\PagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqPageTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $overrides = []): FaqCategory
    {
        return FaqCategory::create(array_merge(['name' => 'Donations', 'is_active' => true], $overrides));
    }

    private function faq(array $overrides = []): Faq
    {
        return Faq::create(array_merge([
            'question' => 'Is my donation tax-deductible?',
            'answer' => 'Yes, under **Section 80G** of the Income Tax Act.',
            'status' => 'published',
            'published_at' => now()->subDay(),
            'admin_notes' => 'PRIVATE-NOTE-MARKER',
        ], $overrides));
    }

    public function test_faq_page_lists_only_published_entries(): void
    {
        $this->seed(PagesSeeder::class);

        $this->faq(['question' => 'Published question?']);
        $this->faq(['question' => 'Draft question?', 'status' => 'draft']);
        $this->faq(['question' => 'Unpublished question?', 'status' => 'unpublished']);
        $this->faq(['question' => 'Archived question?', 'status' => 'archived']);
        $this->faq(['question' => 'Scheduled question?', 'published_at' => now()->addDays(2)]);
        $this->faq(['question' => 'Trashed question?'])->delete();

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('Published question?');
        $response->assertDontSee('Draft question?');
        $response->assertDontSee('Unpublished question?');
        $response->assertDontSee('Archived question?');
        $response->assertDontSee('Scheduled question?');
        $response->assertDontSee('Trashed question?');
    }

    public function test_faqs_in_an_archived_category_are_hidden(): void
    {
        $archived = $this->category(['name' => 'Old', 'is_active' => false]);
        $this->faq(['question' => 'Hidden by category?', 'faq_category_id' => $archived->id]);
        $this->faq(['question' => 'Visible uncategorised?']);

        $this->get(route('faq.index'))
            ->assertOk()
            ->assertSee('Visible uncategorised?')
            ->assertDontSee('Hidden by category?');
    }

    public function test_admin_notes_are_never_rendered_publicly(): void
    {
        $this->faq(['is_featured' => true]);

        $this->get(route('faq.index'))->assertOk()->assertDontSee('PRIVATE-NOTE-MARKER');
        $this->assertArrayNotHasKey('admin_notes', Faq::first()->toArray());
    }

    public function test_answers_render_markdown_safely(): void
    {
        $this->faq(['answer' => 'Yes, under **Section 80G**. <script>alert(1)</script>']);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('<strong>Section 80G</strong>', false);
        $response->assertDontSee('<script>alert(1)</script>', false);
    }

    public function test_category_filter_groups_and_narrows(): void
    {
        $donations = $this->category(['name' => 'Donations']);
        $volunteers = $this->category(['name' => 'Volunteers']);
        $this->faq(['question' => 'Donation question?', 'faq_category_id' => $donations->id]);
        $this->faq(['question' => 'Volunteer question?', 'faq_category_id' => $volunteers->id]);

        $response = $this->get(route('faq.index', ['category' => 'volunteers']));
        $response->assertOk();
        $this->assertSame(['Volunteer question?'], $response->viewData('faqs')->pluck('question')->all());
        $this->assertTrue($response->viewData('currentCategory')->is($volunteers));

        // Unknown slugs fall back to the full list.
        $response = $this->get(route('faq.index', ['category' => 'nope']));
        $response->assertOk();
        $this->assertNull($response->viewData('currentCategory'));
        $this->assertCount(2, $response->viewData('faqs'));
    }

    public function test_search_matches_question_and_answer_and_is_noindexed(): void
    {
        $this->faq(['question' => 'Is my donation tax-deductible?', 'answer' => 'Yes, under Section 80G.']);
        $this->faq(['question' => 'Can I volunteer on weekends?', 'answer' => 'Yes, Sunday drives run weekly.']);

        $response = $this->get(route('faq.index', ['q' => 'Sunday']));
        $response->assertOk();
        $this->assertSame(['Can I volunteer on weekends?'], $response->viewData('faqs')->pluck('question')->all());
        $response->assertSee('1 result for “Sunday”');
        $response->assertSee('<meta name="robots" content="noindex, follow">', false);
        $response->assertDontSee('"@type":"FAQPage"', false);

        $response = $this->get(route('faq.index', ['q' => 'deductible']));
        $this->assertSame(['Is my donation tax-deductible?'], $response->viewData('faqs')->pluck('question')->all());

        $this->get(route('faq.index', ['q' => 'zzzz']))->assertOk()->assertSee('No matching questions');
    }

    public function test_featured_faqs_appear_in_most_asked_and_on_the_homepage(): void
    {
        $this->seed(HomeSectionsSeeder::class);

        $this->faq(['question' => 'Featured question?', 'is_featured' => true]);
        $this->faq(['question' => 'Regular question?', 'is_featured' => false]);

        $response = $this->get(route('faq.index'));
        $response->assertOk()->assertSee('Most Asked');
        $this->assertSame(['Featured question?'], $response->viewData('featured')->pluck('question')->all());

        $this->get(route('home'))->assertOk()->assertSee('Featured question?')->assertDontSee('Regular question?');
    }

    public function test_page_renders_seo_meta_and_faq_structured_data(): void
    {
        $this->seed(PagesSeeder::class);
        $this->faq(['question' => 'Is my donation tax-deductible?', 'answer' => 'Yes, under **Section 80G**.']);

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('<title>Frequently Asked Questions — Gauri Ganesh Seva Sanstha</title>', false);
        $response->assertSee('<link rel="canonical" href="'.route('faq.index').'">', false);
        $response->assertSee('property="og:title"', false);
        $response->assertSee('name="twitter:card"', false);
        $response->assertSee('"@type":"FAQPage"', false);
        $response->assertSee('"name":"Is my donation tax-deductible?"', false);
        // Structured-data answer is plain text, not Markdown/HTML.
        $response->assertSee('"text":"Yes, under Section 80G."', false);
        $response->assertSee('"@type":"BreadcrumbList"', false);
    }

    public function test_accordion_markup_is_accessible(): void
    {
        $faq = $this->faq();

        $response = $this->get(route('faq.index'));

        $response->assertOk();
        $response->assertSee('<button', false);
        $response->assertSee('aria-controls="faq-'.$faq->id.'-panel"', false);
        $response->assertSee(':aria-expanded=', false);
        $response->assertSee('role="region"', false);
        $response->assertSee('aria-labelledby="faq-'.$faq->id.'-header"', false);
    }

    public function test_section_component_uses_category_then_falls_back(): void
    {
        $donations = $this->category(['name' => 'Donations', 'slug' => 'donations']);
        $this->faq(['question' => 'Donation-specific question?', 'faq_category_id' => $donations->id]);
        $this->faq(['question' => 'General featured question?', 'is_featured' => true]);

        // Donate page pulls the "donations" category.
        $this->get(route('donations.donate'))
            ->assertOk()
            ->assertSee('Donation Questions')
            ->assertSee('Donation-specific question?')
            ->assertDontSee('General featured question?');

        // Volunteer page has no "volunteers" category → falls back to featured.
        $this->get(route('volunteer.create'))
            ->assertOk()
            ->assertSee('Volunteering Questions')
            ->assertSee('General featured question?');
    }

    public function test_public_cache_is_invalidated_when_a_faq_changes(): void
    {
        $faq = $this->faq(['question' => 'Original question?']);

        $this->get(route('faq.index'))->assertSee('Original question?');

        app(FaqService::class)->unpublish($faq);

        $this->get(route('faq.index'))->assertDontSee('Original question?');
    }
}
