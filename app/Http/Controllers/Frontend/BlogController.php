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
use App\Support\Seo\StructuredData;
use Illuminate\Database\Eloquent\Model;
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
        return $this->listing($request, $request->only(['q']), title: 'Blog & News', description: 'Stories, updates and announcements from '.setting('general.site_name').'.', baseUrl: route('blog.index'));
    }

    public function category(Request $request, BlogCategory $category): View
    {
        abort_unless($category->is_active, 404);

        return $this->listing(
            $request,
            $request->only(['q']) + ['category' => $category->slug],
            heading: $category->name,
            subheading: $category->description,
            title: $category->name,
            description: $category->description ?: $category->name.' — articles and updates from '.setting('general.site_name').'.',
            baseUrl: route('blog.category', $category),
            crumb: $category->name,
            model: $category,
        );
    }

    public function tag(Request $request, BlogTag $tag): View
    {
        // Tag pages are thin duplicates of the main listing — keep them
        // crawlable but out of the index.
        return $this->listing(
            $request,
            $request->only(['q']) + ['tag' => $tag->slug],
            heading: '#'.$tag->name,
            title: 'Posts tagged '.$tag->name,
            description: 'Articles tagged '.$tag->name.'.',
            baseUrl: route('blog.tag', $tag),
            crumb: '#'.$tag->name,
            noindex: true,
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

        $post->load(['category', 'author', 'tags', 'media', 'seo.ogImage', 'seo.twitterImage']);
        $url = route('blog.show', $post);
        $image = $post->getFirstMedia('featured_image')?->getUrl();

        return view('frontend.blog.show', [
            'post' => $post,
            'seo' => $this->seo()->forModel($post, [
                'title' => $post->title,
                'description' => $post->excerpt,
                'canonical' => $url,
                'image' => $image,
                'ogType' => 'article',
                'publishedTime' => $post->published_at?->toIso8601String(),
                'modifiedTime' => $post->updated_at?->toIso8601String(),
                'breadcrumbs' => array_values(array_filter([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Blog', 'url' => route('blog.index')],
                    $post->category ? ['label' => $post->category->name, 'url' => route('blog.category', $post->category)] : null,
                    ['label' => $post->title],
                ])),
                'schemas' => [StructuredData::blogPost($post, $url, $image)],
            ]),
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
    private function listing(Request $request, array $filters, ?string $heading = null, ?string $subheading = null, ?string $title = null, ?string $description = null, ?string $baseUrl = null, ?string $crumb = null, ?Model $model = null, bool $noindex = false): View
    {
        $breadcrumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Blog', 'url' => $crumb ? route('blog.index') : null]];
        if ($crumb) {
            $breadcrumbs[] = ['label' => $crumb];
        }

        $seo = $this->seo()->listing($request, $baseUrl ?? route('blog.index'), [
            'title' => $title,
            'description' => $description,
            'breadcrumbs' => $breadcrumbs,
            'noindex' => $noindex,
            'schemas' => [StructuredData::webPage('CollectionPage', $title ?? 'Blog', $baseUrl ?? route('blog.index'), $description)],
        ], [], $model);

        return view('frontend.blog.index', [
            'seo' => $seo,
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
