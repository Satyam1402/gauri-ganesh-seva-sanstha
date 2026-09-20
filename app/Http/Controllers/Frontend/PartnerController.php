<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\PartnerRepositoryInterface;
use App\Interfaces\PartnerTypeRepositoryInterface;
use App\Models\Partner;
use App\Support\Seo\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function __construct(
        private PartnerRepositoryInterface $partners,
        private PartnerTypeRepositoryInterface $types,
    ) {}

    /**
     * Dedicated public partners page. The Page SEO anchor is cached
     * forever (SeoService busts it); the lists are cached by the repository.
     */
    public function index(Request $request): View
    {
        $types = $this->types->activeOrdered();

        // Unknown slugs collapse to "all" rather than an empty page.
        $currentType = $types->firstWhere('slug', $request->query('type'));

        $partners = $this->partners->publicList($currentType?->slug);

        return view('frontend.partners.index', [
            'seo' => $this->seo()->listing($request, route('partners.index'), [
                'title' => $currentType ? $currentType->name.'s' : 'Partners & Sponsors',
                'description' => 'The organisations, companies, institutions and sponsors who work alongside '.setting('general.site_name').' to serve the community.',
                'breadcrumbs' => array_values(array_filter([['label' => 'Home', 'url' => route('home')], ['label' => 'Partners & Sponsors', 'url' => $currentType ? route('partners.index') : null], $currentType ? ['label' => $currentType->name] : null])),
                'schemas' => [StructuredData::webPage('CollectionPage', 'Partners & Sponsors', route('partners.index'))],
            ], ['type'], $currentType ? null : $this->seo()->pageModel('partners')),
            'types' => $types,
            'partners' => $partners,
            // Grouped by type for the browse view; untyped partners last.
            'groups' => $partners->groupBy(fn ($partner) => $partner->partner_type_id ?? 0)
                ->sortBy(fn ($group, $key) => $key === 0 ? PHP_INT_MAX : ($group->first()->type->order_column ?? 0)),
            'featured' => $currentType === null ? $this->partners->sectionList(null, true, 8) : collect(),
            'currentType' => $currentType,
        ]);
    }

    public function show(Partner $partner): View
    {
        $partner->load(['type', 'media']);

        abort_unless($partner->isActive() && ($partner->partner_type_id === null || $partner->type?->is_active), 404);

        $url = route('partners.show', $partner);
        $cover = $partner->getFirstMedia('cover_image') ?? $partner->getFirstMedia('logo');

        return view('frontend.partners.show', [
            'seo' => $this->seo()->make([
                'title' => $partner->name,
                'description' => $partner->short_description ?: $partner->name.' — a '.($partner->type?->name ?? 'partner').' of '.setting('general.site_name').'.',
                'canonical' => $url,
                'image' => $cover?->getUrl(),
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Partners & Sponsors', 'url' => route('partners.index')], ['label' => $partner->name]],
                'schemas' => [StructuredData::webPage('WebPage', $partner->name, $url, $partner->short_description)],
            ]),
            'partner' => $partner,
            'related' => $this->partners->related($partner, 4),
        ]);
    }
}
