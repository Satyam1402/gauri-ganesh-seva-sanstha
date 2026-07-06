<?php

namespace Tests\Feature;

use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BlogPageTest extends TestCase
{
    use RefreshDatabase;

    private function category(array $overrides = []): BlogCategory
    {
        return BlogCategory::create(array_merge([
            'name' => 'Success Stories',
            'is_active' => true,
        ], $overrides));
    }

    private function makePost(array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'blog_category_id' => $this->category()->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'A Story of Change',
            'excerpt' => 'How one scholarship changed a life.',
            'content' => "## The story\n\nIt began with a single sponsor.",
            'published_at' => now()->subDay(),
            'reading_minutes' => 1,
            'allow_comments' => true,
            'status' => 'published',
        ], $overrides));
    }

    public function test_blog_index_lists_published_posts_and_hides_drafts(): void
    {
        $this->makePost(['title' => 'Published Story']);
        $this->makePost(['title' => 'Draft Story', 'status' => 'draft', 'published_at' => null]);

        $response = $this->get(route('blog.index'));

        $response->assertOk();
        $response->assertSee('Published Story');
        $response->assertDontSee('Draft Story');
    }

    public function test_a_scheduled_post_is_hidden_from_the_listing_and_returns_404(): void
    {
        $scheduled = $this->makePost(['title' => 'Scheduled Story', 'published_at' => now()->addWeek()]);

        $this->get(route('blog.index'))->assertOk()->assertDontSee('Scheduled Story');
        $this->get(route('blog.show', $scheduled))->assertNotFound();
    }

    public function test_category_page_shows_only_posts_from_that_category(): void
    {
        $stories = $this->category(['name' => 'Stories A']);
        $news = $this->category(['name' => 'News B']);

        $this->makePost(['title' => 'Story Post', 'blog_category_id' => $stories->id]);
        $this->makePost(['title' => 'News Post', 'blog_category_id' => $news->id]);

        $response = $this->get(route('blog.category', $stories));

        $response->assertOk();
        // The popular-posts sidebar shows all published posts, so assert
        // against the listing data rather than the full page HTML.
        $titles = $response->viewData('posts')->pluck('title');
        $this->assertTrue($titles->contains('Story Post'));
        $this->assertFalse($titles->contains('News Post'));
    }

    public function test_an_inactive_category_page_returns_404(): void
    {
        $category = $this->category(['is_active' => false]);

        $this->get(route('blog.category', $category))->assertNotFound();
    }

    public function test_tag_page_shows_only_posts_with_that_tag(): void
    {
        $tag = BlogTag::create(['name' => 'health']);

        $tagged = $this->makePost(['title' => 'Tagged Post']);
        $tagged->tags()->attach($tag);
        $this->makePost(['title' => 'Untagged Post']);

        $response = $this->get(route('blog.tag', $tag));

        $response->assertOk();
        $titles = $response->viewData('posts')->pluck('title');
        $this->assertTrue($titles->contains('Tagged Post'));
        $this->assertFalse($titles->contains('Untagged Post'));
    }

    public function test_search_filters_the_listing(): void
    {
        $this->makePost(['title' => 'Winter Blanket Drive']);
        $this->makePost(['title' => 'Medical Camp Report', 'excerpt' => 'Checkups for all.']);

        $response = $this->get(route('blog.index', ['q' => 'blanket']));

        $response->assertOk();
        $titles = $response->viewData('posts')->pluck('title');
        $this->assertTrue($titles->contains('Winter Blanket Drive'));
        $this->assertFalse($titles->contains('Medical Camp Report'));
    }

    public function test_a_draft_post_detail_page_returns_404(): void
    {
        $draft = $this->makePost(['status' => 'draft', 'published_at' => null]);

        $this->get(route('blog.show', $draft))->assertNotFound();
    }

    public function test_a_published_post_detail_page_renders_content_and_seo_markup(): void
    {
        $post = $this->makePost();

        $response = $this->get(route('blog.show', $post));

        $response->assertOk();
        $response->assertSee('A Story of Change');
        $response->assertSee('<h2>The story</h2>', false);
        $response->assertSee('schema.org', false);
        $response->assertSee('Leave a Comment');
    }

    public function test_viewing_a_post_increments_views_once_per_session(): void
    {
        $post = $this->makePost();

        $this->get(route('blog.show', $post));
        $this->assertSame(1, $post->fresh()->views_count);

        $this->get(route('blog.show', $post));
        $this->assertSame(1, $post->fresh()->views_count);
    }

    public function test_a_visitor_can_submit_a_comment_which_lands_as_pending(): void
    {
        $post = $this->makePost();

        $response = $this->post(route('blog.comments.store', $post), [
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'body' => 'Wonderful work, keep it up!',
        ]);

        $response->assertRedirect(route('blog.show', $post));
        $response->assertSessionHas('comment_status');

        $comment = $post->comments()->firstOrFail();
        $this->assertSame('Asha Patil', $comment->name);
        $this->assertSame('pending', $comment->status->value);
    }

    public function test_pending_comments_are_not_shown_on_the_post_page(): void
    {
        $post = $this->makePost();
        $post->comments()->create([
            'name' => 'Hidden Commenter',
            'email' => 'hidden@example.com',
            'body' => 'A pending comment.',
            'status' => 'pending',
        ]);
        $post->comments()->create([
            'name' => 'Visible Commenter',
            'email' => 'visible@example.com',
            'body' => 'An approved comment.',
            'status' => 'approved',
        ]);

        $response = $this->get(route('blog.show', $post));

        $response->assertOk();
        $response->assertSee('Visible Commenter');
        $response->assertDontSee('Hidden Commenter');
    }

    public function test_comments_are_rejected_when_disabled_for_the_post(): void
    {
        $post = $this->makePost(['allow_comments' => false]);

        $response = $this->post(route('blog.comments.store', $post), [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'body' => 'This should not be accepted.',
        ]);

        $response->assertSessionHasErrors('comment');
        $this->assertSame(0, $post->comments()->count());
    }
}
