<?php

namespace App\Http\Controllers\Admin;

use App\Enums\CampaignStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderDonationCampaignsRequest;
use App\Http\Requests\Admin\StoreDonationCampaignRequest;
use App\Http\Requests\Admin\UpdateDonationCampaignRequest;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\DonationCampaign;
use App\Services\DonationCampaignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DonationCampaignController extends Controller
{
    public function __construct(
        private DonationCampaignRepositoryInterface $campaigns,
        private DonationCampaignService $campaignService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', DonationCampaign::class);

        $filters = $request->only(['q', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.donation-campaigns.index', [
            'campaigns' => $this->campaigns->adminSearch($filters, 15),
            'statuses' => CampaignStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', DonationCampaign::class);

        return view('admin.donation-campaigns.create', [
            'statuses' => CampaignStatus::options(),
        ]);
    }

    public function store(StoreDonationCampaignRequest $request): RedirectResponse
    {
        $this->authorize('create', DonationCampaign::class);

        $campaign = $this->campaignService->createCampaign($request->validated());

        return redirect()->route('admin.donation-campaigns.edit', $campaign)
            ->with('status', 'Campaign created successfully.');
    }

    public function edit(DonationCampaign $donationCampaign): View
    {
        $this->authorize('update', $donationCampaign);

        return view('admin.donation-campaigns.edit', [
            'campaign' => $donationCampaign->load(['media', 'seo'])->loadCount('donations'),
            'statuses' => CampaignStatus::options(),
        ]);
    }

    public function update(UpdateDonationCampaignRequest $request, DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('update', $donationCampaign);

        $this->campaignService->updateCampaign($donationCampaign, $request->validated());

        return redirect()->route('admin.donation-campaigns.edit', $donationCampaign)
            ->with('status', 'Campaign updated successfully.');
    }

    public function destroy(DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('delete', $donationCampaign);

        $this->campaignService->deleteCampaign($donationCampaign);

        return redirect()->route('admin.donation-campaigns.index')
            ->with('status', 'Campaign moved to trash.');
    }

    public function restore(DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('restore', $donationCampaign);

        $this->campaignService->restoreCampaign($donationCampaign);

        return redirect()->route('admin.donation-campaigns.index', ['trashed' => 1])
            ->with('status', 'Campaign restored successfully.');
    }

    public function toggleFeatured(DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('update', $donationCampaign);

        $this->campaignService->toggleFeatured($donationCampaign);

        return back()->with('status', $donationCampaign->is_featured ? 'Campaign marked as featured.' : 'Campaign removed from featured.');
    }

    public function activate(DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('update', $donationCampaign);

        $this->campaignService->activate($donationCampaign);

        return back()->with('status', 'Campaign activated.');
    }

    public function archive(DonationCampaign $donationCampaign): RedirectResponse
    {
        $this->authorize('update', $donationCampaign);

        $this->campaignService->archive($donationCampaign);

        return back()->with('status', 'Campaign archived.');
    }

    public function reorder(ReorderDonationCampaignsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', DonationCampaign::class);

        $this->campaignService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
