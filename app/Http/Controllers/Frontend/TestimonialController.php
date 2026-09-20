<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\TestimonialType;
use App\Http\Controllers\Controller;
use App\Interfaces\TestimonialRepositoryInterface;
use App\Support\Seo\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(private TestimonialRepositoryInterface $testimonials) {}

    /**
     * Dedicated public testimonials page. The Page SEO anchor is cached
     * forever (SeoService busts it); the lists are cached by the repository.
     */
    public function index(Request $request): View
    {
        $type = in_array($request->query('type'), TestimonialType::values(), true)
            ? TestimonialType::from($request->query('type'))
            : null;

        return view('frontend.testimonials.index', [
            'seo' => $this->seo()->listing($request, route('testimonials.index'), [
                'title' => $type ? $type->pluralLabel() : 'Testimonials',
                'description' => 'Real stories from the people we serve, our donors, volunteers and partners — hear firsthand what '.setting('general.site_name').' means to the community.',
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Testimonials']],
                'schemas' => [StructuredData::webPage('CollectionPage', 'Testimonials', route('testimonials.index'))],
            ], [], $type ? null : $this->seo()->pageModel('testimonials')),
            'testimonials' => $this->testimonials->publicPaginated(['type' => $type?->value], 12),
            // Featured strip only on the unfiltered first page — it would
            // duplicate cards otherwise.
            'featured' => $type === null && $request->integer('page', 1) === 1
                ? $this->testimonials->sectionList(null, true, 3)
                : collect(),
            'typeCounts' => $this->testimonials->publicTypeCounts(),
            'currentType' => $type,
        ]);
    }
}
