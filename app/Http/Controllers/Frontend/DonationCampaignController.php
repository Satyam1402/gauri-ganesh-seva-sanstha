<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\DonationCampaign;
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
        ]);
    }

    public function show(DonationCampaign $campaign): View
    {
        abort_unless(in_array($campaign->status, [CampaignStatus::Active, CampaignStatus::Completed], true), 404);

        return view('frontend.donations.show', [
            'campaign' => $campaign->load(['media', 'seo.ogImage']),
            'recentDonations' => $campaign->completedDonations()
                ->orderBy('donated_at', 'desc')
                ->limit(8)
                ->get(),
            'others' => $this->campaigns->featuredList(3)->reject(fn ($item) => $item->id === $campaign->id)->take(2),
        ]);
    }
}
