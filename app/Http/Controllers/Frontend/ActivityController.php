<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Interfaces\ActivityRepositoryInterface;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Support\Seo\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepositoryInterface $activities,
        private ActivityCategoryRepositoryInterface $categories,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'sort']);
        $categories = $this->categories->activeOrdered();
        $category = ! empty($filters['category']) ? $categories->firstWhere('slug', $filters['category']) : null;

        $breadcrumbs = [['label' => 'Home', 'url' => route('home')], ['label' => 'Activities', 'url' => $category ? route('activities.index') : null]];
        if ($category) {
            $breadcrumbs[] = ['label' => $category->name];
        }

        return view('frontend.activities.index', [
            'activities' => $this->activities->publishedPaginated($filters, 12),
            'categories' => $categories,
            'filters' => $filters,
            'latest' => $this->activities->latest(3),
            // A category filter is a distinct, indexable page with its own SEO row.
            'seo' => $this->seo()->listing($request, route('activities.index'), [
                'title' => $category ? $category->name.' Activities' : 'Our Activities',
                'description' => $category?->description ?: 'Real programmes, real impact — food distribution, education support, medical camps and community welfare work by '.setting('general.site_name').'.',
                'breadcrumbs' => $breadcrumbs,
                'schemas' => [StructuredData::webPage('CollectionPage', $category ? $category->name.' Activities' : 'Our Activities', $category ? route('activities.index', ['category' => $category->slug]) : route('activities.index'))],
            ], ['category'], $category instanceof ActivityCategory ? $category : null),
        ]);
    }

    public function show(Activity $activity): View
    {
        abort_unless($activity->status->value === 'published', 404);

        $activity->load(['category', 'media', 'seo.ogImage', 'seo.twitterImage']);
        $url = route('activities.show', $activity);
        $image = $activity->getFirstMedia('featured_image')?->getUrl();

        return view('frontend.activities.show', [
            'activity' => $activity,
            'related' => $this->activities->related($activity, 3),
            'seo' => $this->seo()->forModel($activity, [
                'title' => $activity->title,
                'description' => $activity->short_description,
                'canonical' => $url,
                'image' => $image,
                'ogType' => 'article',
                'publishedTime' => $activity->activity_date?->toIso8601String(),
                'modifiedTime' => $activity->updated_at?->toIso8601String(),
                'breadcrumbs' => array_values(array_filter([
                    ['label' => 'Home', 'url' => route('home')],
                    ['label' => 'Activities', 'url' => route('activities.index')],
                    $activity->category ? ['label' => $activity->category->name, 'url' => route('activities.index', ['category' => $activity->category->slug])] : null,
                    ['label' => $activity->title],
                ])),
                'schemas' => [StructuredData::article('Article', $activity->title, $url, $activity->short_description, $image, $activity->activity_date?->toIso8601String(), $activity->updated_at?->toIso8601String())],
            ]),
        ]);
    }
}
