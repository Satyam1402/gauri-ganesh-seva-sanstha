<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StoreBlogCommentRequest;
use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Interfaces\BlogPostRepositoryInterface;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Services\BlogCommentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BlogController extends Controller
{
    public function __construct(
        private BlogPostRepositoryInterface $posts,
        private BlogCategoryRepositoryInterface $categories,
        private BlogCommentService $commentService,
    ) {}

    public function index(Request $request): View
    {
        return $this->listing($request->only(['q']));
    }

    public function category(Request $request, BlogCategory $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->listing(
            $request->only(['q']) + ['category' => $category->slug],
            heading: $category->name,
            subheading: $category->description,
        );
    }

    public function tag(Request $request, BlogTag $tag): View
    {
        return $this->listing(
            $request->only(['q']) + ['tag' => $tag->slug],
            heading: '#'.$tag->name,
        );
    }

    public function show(Request $request, BlogPost $post): View
    {
        abort_unless($post->isLive(), 404);

        // Count each visitor session once so refreshes don't inflate views.
        $sessionKey = "blog_viewed.{$post->id}";
        if (! $request->session()->has($sessionKey)) {
            $this->posts->incrementViews($post);
            $request->session()->put($sessionKey, true);
        }

        return view('frontend.blog.show', [
            'post' => $post->load(['category', 'author', 'tags', 'media', 'seo.ogImage']),
            'comments' => $post->allow_comments ? $post->approvedComments()->get() : collect(),
            'related' => $this->posts->related($post, 3),
            'popular' => $this->posts->popular(5),
        ]);
    }

    public function storeComment(StoreBlogCommentRequest $request, BlogPost $post): RedirectResponse
    {
        abort_unless($post->isLive(), 404);

        $this->commentService->submit($post, $request->validated(), $request->ip());

        return redirect()->route('blog.show', $post)
            ->with('comment_status', 'Thank you! Your comment has been submitted and will appear once approved.');
    }

    /**
     * Shared listing renderer for the index, category, and tag pages.
     *
     * @param  array<string, mixed>  $filters
     */
    private function listing(array $filters, ?string $heading = null, ?string $subheading = null): View
    {
        return view('frontend.blog.index', [
            'posts' => $this->posts->publishedPaginated($filters, 9),
            'categories' => $this->categories->activeOrdered(),
            'featured' => $this->posts->featuredList(3),
            'popular' => $this->posts->popular(5),
            'filters' => $filters,
            'heading' => $heading,
            'subheading' => $subheading,
        ]);
    }
}
