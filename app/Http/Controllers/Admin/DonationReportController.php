<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Interfaces\DonationRepositoryInterface;
use App\Models\Donation;
use Illuminate\View\View;

class DonationReportController extends Controller
{
    public function __construct(
        private DonationRepositoryInterface $donations,
        private DonationCampaignRepositoryInterface $campaigns,
    ) {}

    public function index(): View
    {
        $this->authorize('viewReports', Donation::class);

        return view('admin.donations.reports', [
            'summary' => $this->donations->revenueSummary(),
            'monthly' => $this->donations->monthlyTotals(12),
            'topDonors' => $this->donations->topDonors(10),
            'campaignProgress' => $this->campaigns->allOrdered(),
            'recentDonations' => $this->donations->recentCompleted(10),
        ]);
    }
}
