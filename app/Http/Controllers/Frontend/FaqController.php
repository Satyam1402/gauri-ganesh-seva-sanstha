<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\FaqCategoryRepositoryInterface;
use App\Interfaces\FaqRepositoryInterface;
use App\Support\Seo\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(
        private FaqRepositoryInterface $faqs,
        private FaqCategoryRepositoryInterface $categories,
    ) {}

    /**
     * Dedicated public FAQ page. The Page SEO anchor is cached forever
     * (SeoService busts it); browse lists are cached by the repository,
     * searches are not.
     */
    public function index(Request $request): View
    {
        $categories = $this->categories->activeOrdered();

        $filters = [
            // Cap the term so pathological input can't hit the search path.
            'q' => mb_substr(trim((string) $request->query('q', '')), 0, 100),
            // Unknown slugs collapse to "all" rather than an empty page.
            'category' => $categories->firstWhere('slug', $request->query('category'))?->slug ?? '',
        ];

        $faqs = $this->faqs->publicList($filters);
        $isBrowsingAll = $filters['q'] === '' && $filters['category'] === '';

        $currentCategory = $filters['category'] !== '' ? $categories->firstWhere('slug', $filters['category']) : null;
        $isSearching = $filters['q'] !== '';
        $faqTitle = $currentCategory ? $currentCategory->name.' FAQs' : 'Frequently Asked Questions';

        return view('frontend.faq.index', [
            'seo' => $this->seo()->listing($request, route('faq.index'), [
                'title' => $faqTitle,
                'description' => $currentCategory?->description ?: 'Answers to common questions about donating, volunteering, our programmes and how to get support from '.setting('general.site_name').'.',
                'breadcrumbs' => array_values(array_filter([['label' => 'Home', 'url' => route('home')], ['label' => 'FAQ', 'url' => $currentCategory ? route('faq.index') : null], $currentCategory ? ['label' => $currentCategory->name] : null])),
                // FAQPage only for questions actually rendered, never on search results.
                'schemas' => [! $isSearching && $faqs->isNotEmpty() ? StructuredData::faqPage($faqTitle, route('faq.index'), $faqs) : null],
            ], ['category'], $currentCategory === null && ! $isSearching ? $this->seo()->pageModel('faq') : null),
            'categories' => $categories,
            'faqs' => $faqs,
            // Grouped by category for the browse view; uncategorised last.
            'groups' => $faqs->groupBy(fn ($faq) => $faq->faq_category_id ?? 0)
                ->sortBy(fn ($group, $key) => $key === 0 ? PHP_INT_MAX : ($group->first()->category->order_column ?? 0)),
            'featured' => $isBrowsingAll ? $this->faqs->sectionList(null, true, 5) : collect(),
            'filters' => $filters,
            'currentCategory' => $filters['category'] !== '' ? $categories->firstWhere('slug', $filters['category']) : null,
        ]);
    }
}
