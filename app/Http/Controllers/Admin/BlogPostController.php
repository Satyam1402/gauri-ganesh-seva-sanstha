<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PostStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteBlogPostsRequest;
use App\Http\Requests\Admin\BulkPublishBlogPostsRequest;
use App\Http\Requests\Admin\StoreBlogPostRequest;
use App\Http\Requests\Admin\UpdateBlogPostRequest;
use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Interfaces\BlogPostRepositoryInterface;
use App\Models\BlogPost;
use App\Models\User;
use App\Services\BlogPostService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogPostController extends Controller
{
    public function __construct(
        private BlogPostRepositoryInterface $posts,
        private BlogCategoryRepositoryInterface $categories,
        private BlogPostService $postService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', BlogPost::class);

        $filters = $request->only(['q', 'category', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.blog-posts.index', [
            'posts' => $this->posts->adminSearch($filters, 15),
            'categories' => $this->categories->allOrdered(),
            // "scheduled" is a virtual filter: published with a future date.
            'statuses' => PostStatus::options() + ['scheduled' => 'Scheduled'],
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BlogPost::class);

        return view('admin.blog-posts.create', [
            'categories' => $this->categories->allOrdered(),
            'statuses' => PostStatus::options(),
            'authors' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function store(StoreBlogPostRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogPost::class);

        $post = $this->postService->createPost($request->validated(), $request->user()->id);

        return redirect()->route('admin.blog-posts.edit', $post)
            ->with('status', 'Post created successfully.');
    }

    public function edit(BlogPost $blogPost): View
    {
        $this->authorize('update', $blogPost);

        return view('admin.blog-posts.edit', [
            'post' => $blogPost->load(['category', 'author', 'tags', 'media', 'seo'])->loadCount('comments'),
            'categories' => $this->categories->allOrdered(),
            'statuses' => PostStatus::options(),
            'authors' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateBlogPostRequest $request, BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('update', $blogPost);

        $this->postService->updatePost($blogPost, $request->validated());

        return redirect()->route('admin.blog-posts.edit', $blogPost)
            ->with('status', 'Post updated successfully.');
    }

    public function destroy(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('delete', $blogPost);

        $this->postService->deletePost($blogPost);

        return redirect()->route('admin.blog-posts.index')
            ->with('status', 'Post moved to trash.');
    }

    public function restore(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('restore', $blogPost);

        $this->postService->restorePost($blogPost);

        return redirect()->route('admin.blog-posts.index', ['trashed' => 1])
            ->with('status', 'Post restored successfully.');
    }

    public function toggleFeatured(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('update', $blogPost);

        $this->postService->toggleFeatured($blogPost);

        return back()->with('status', $blogPost->is_featured ? 'Post marked as featured.' : 'Post removed from featured.');
    }

    public function publish(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('update', $blogPost);

        $this->postService->publish($blogPost);

        return back()->with('status', 'Post published.');
    }

    public function unpublish(BlogPost $blogPost): RedirectResponse
    {
        $this->authorize('update', $blogPost);

        $this->postService->unpublish($blogPost);

        return back()->with('status', 'Post moved back to draft.');
    }

    public function bulkDestroy(BulkDeleteBlogPostsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', BlogPost::class);

        $count = $this->postService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} posts moved to trash.");
    }

    public function bulkPublish(BulkPublishBlogPostsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', BlogPost::class);

        $count = $this->postService->bulkPublish($request->validated('ids'));

        return back()->with('status', "{$count} posts published.");
    }
}
