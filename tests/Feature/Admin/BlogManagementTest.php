<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BlogManagementTest extends TestCase
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

    private function category(): BlogCategory
    {
        return BlogCategory::create(['name' => 'Success Stories', 'is_active' => true]);
    }

    private function makePost(BlogCategory $category, array $overrides = []): BlogPost
    {
        return BlogPost::create(array_merge([
            'blog_category_id' => $category->id,
            'user_id' => $this->admin()->id,
            'title' => 'A Story of Change',
            'excerpt' => 'How one scholarship changed a life.',
            'content' => 'It began with a single sponsor.',
            'reading_minutes' => 1,
            'status' => 'draft',
        ], $overrides));
    }

    public function test_user_without_manage_blog_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.blog-posts.index'))->assertForbidden();
    }

    public function test_content_manager_can_create_a_post_with_images_tags_and_seo(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $category = $this->category();

        $response = $this->actingAs($contentManager)->post(route('admin.blog-posts.store'), [
            'blog_category_id' => $category->id,
            'title' => 'Winter Blanket Drive 2026',
            'excerpt' => 'Blankets for 1,200 families.',
            'content' => str_repeat('Every winter our volunteers head out with blankets. ', 50),
            'tags' => 'winter drive, relief',
            'published_at' => now()->subHour()->format('Y-m-d\TH:i'),
            'allow_comments' => 1,
            'status' => 'published',
            'is_featured' => 1,
            'featured_image' => UploadedFile::fake()->image('drive.jpg'),
            'gallery' => [UploadedFile::fake()->image('gallery1.jpg')],
            'meta_title' => 'Custom SEO Title',
            'meta_description' => 'Custom SEO description.',
        ]);

        $post = BlogPost::firstOrFail();
        $response->assertRedirect(route('admin.blog-posts.edit', $post));

        $this->assertSame('Winter Blanket Drive 2026', $post->title);
        $this->assertSame('winter-blanket-drive-2026', $post->slug);
        $this->assertTrue($post->is_featured);
        $this->assertTrue($post->allow_comments);
        $this->assertGreaterThanOrEqual(1, $post->reading_minutes);
        $this->assertSame(['relief', 'winter drive'], $post->tags->pluck('name')->sort()->values()->all());
        $this->assertSame('Custom SEO Title', $post->seo->meta_title);
        $this->assertNotNull($post->getFirstMedia('featured_image'));
        $this->assertCount(1, $post->getMedia('gallery'));
    }

    public function test_a_featured_image_is_required_when_creating_a_post(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.blog-posts.store'), [
            'title' => 'No Image Post',
            'excerpt' => 'Missing its featured image.',
            'content' => 'Body.',
            'status' => 'draft',
        ]);

        $response->assertSessionHasErrors('featured_image');
        $this->assertSame(0, BlogPost::count());
    }

    public function test_publishing_a_post_without_a_date_backfills_published_at(): void
    {
        $admin = $this->admin();
        $post = $this->makePost($this->category());

        $this->actingAs($admin)->patch(route('admin.blog-posts.publish', $post))->assertRedirect();

        $post->refresh();
        $this->assertSame('published', $post->status->value);
        $this->assertNotNull($post->published_at);

        $this->actingAs($admin)->patch(route('admin.blog-posts.unpublish', $post))->assertRedirect();
        $this->assertSame('draft', $post->fresh()->status->value);
    }

    public function test_toggling_featured_flips_the_flag(): void
    {
        $post = $this->makePost($this->category());
        $this->assertFalse($post->fresh()->is_featured);

        $this->actingAs($this->admin())->patch(route('admin.blog-posts.feature', $post))->assertRedirect();
        $this->assertTrue($post->fresh()->is_featured);
    }

    public function test_deleting_a_post_soft_deletes_it_and_it_can_be_restored(): void
    {
        $admin = $this->admin();
        $post = $this->makePost($this->category());

        $this->actingAs($admin)->delete(route('admin.blog-posts.destroy', $post))->assertRedirect();
        $this->assertSoftDeleted($post);

        $this->actingAs($admin)->patch(route('admin.blog-posts.restore', $post))->assertRedirect();
        $this->assertNull($post->fresh()->deleted_at);
    }

    public function test_bulk_publish_updates_multiple_posts_and_backfills_dates(): void
    {
        $category = $this->category();
        $first = $this->makePost($category, ['title' => 'Post One']);
        $second = $this->makePost($category, ['title' => 'Post Two']);

        $this->actingAs($this->admin())->post(route('admin.blog-posts.bulk-publish'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSame('published', $first->fresh()->status->value);
        $this->assertSame('published', $second->fresh()->status->value);
        $this->assertNotNull($first->fresh()->published_at);
    }

    public function test_bulk_delete_soft_deletes_multiple_posts(): void
    {
        $category = $this->category();
        $first = $this->makePost($category, ['title' => 'Post One']);
        $second = $this->makePost($category, ['title' => 'Post Two']);

        $this->actingAs($this->admin())->post(route('admin.blog-posts.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_a_comment_can_be_approved_and_deleted(): void
    {
        $admin = $this->admin();
        $post = $this->makePost($this->category());
        $comment = $post->comments()->create([
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'body' => 'Wonderful work!',
            'status' => 'pending',
        ]);

        $this->actingAs($admin)->put(route('admin.blog-comments.update', $comment), [
            'status' => 'approved',
        ])->assertRedirect();
        $this->assertSame('approved', $comment->fresh()->status->value);

        $this->actingAs($admin)->delete(route('admin.blog-comments.destroy', $comment))->assertRedirect();
        $this->assertNull($comment->fresh());
    }

    public function test_blog_category_can_be_created_and_reordered(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.blog-categories.store'), [
            'name' => 'Medical Camps',
            'is_active' => 1,
        ])->assertRedirect(route('admin.blog-categories.index'));

        $first = BlogCategory::where('slug', 'medical-camps')->firstOrFail();
        $second = BlogCategory::create(['name' => 'Awareness', 'is_active' => true]);

        $this->actingAs($admin)->postJson(route('admin.blog-categories.reorder'), [
            'order' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertSame(0, $second->fresh()->order_column);
        $this->assertSame(1, $first->fresh()->order_column);
    }

    public function test_a_category_with_posts_cannot_be_deleted(): void
    {
        $category = $this->category();
        $this->makePost($category);

        $this->actingAs($this->admin())
            ->from(route('admin.blog-categories.index'))
            ->delete(route('admin.blog-categories.destroy', $category))
            ->assertSessionHasErrors('category');

        $this->assertNotNull(BlogCategory::find($category->id));
    }
}
