<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\DonationCampaign;
use App\Support\Seo\StructuredData;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationCampaignController extends Controller
{
    public function __construct(
        private DonationCampaignRepositoryInterface $campaigns,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'sort']);

        return view('frontend.donations.index', [
            'campaigns' => $this->campaigns->activePaginated($filters, 9),
            'featured' => $this->campaigns->featuredList(3),
            'filters' => $filters,
            'seo' => $this->seo()->listing($request, route('donations.campaigns.index'), [
                'title' => 'Donation Campaigns',
                'description' => 'Support a specific cause — every campaign shows exactly where your contribution goes.',
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Campaigns']],
                'schemas' => [StructuredData::webPage('CollectionPage', 'Donation Campaigns', route('donations.campaigns.index'))],
            ]),
        ]);
    }

    public function show(DonationCampaign $campaign): View
    {
        abort_unless(in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true), 404);

        $campaign->load(['media', 'seo.ogImage', 'seo.twitterImage']);
        $url = route('donations.campaigns.show', $campaign);

        return view('frontend.donations.show', [
            'campaign' => $campaign,
            'seo' => $this->seo()->forModel($campaign, [
                'title' => $campaign->name,
                'description' => $campaign->short_description,
                'canonical' => $url,
                'image' => $campaign->getFirstMedia('featured_image')?->getUrl(),
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'Campaigns', 'url' => route('donations.campaigns.index')], ['label' => $campaign->name]],
                'schemas' => [StructuredData::webPage('WebPage', $campaign->name, $url, $campaign->short_description)],
            ]),
            'recentDonations' => $campaign->completedDonations()
                ->orderBy('donated_at', 'desc')
                ->limit(8)
                ->get(),
            'others' => $this->campaigns->featuredList(3)->reject(fn ($item) => $item->id === $campaign->id)->take(2),
        ]);
    }
}
