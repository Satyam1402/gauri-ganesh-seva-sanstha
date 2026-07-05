<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreDonationRequest;
use App\Http\Requests\Admin\UpdateDonationRequest;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Interfaces\DonationRepositoryInterface;
use App\Models\Donation;
use App\Services\DonationExportService;
use App\Services\DonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class DonationController extends Controller
{
    public function __construct(
        private DonationRepositoryInterface $donations,
        private DonationCampaignRepositoryInterface $campaigns,
        private DonationService $donationService,
        private DonationExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Donation::class);

        $filters = $request->only(['q', 'campaign', 'status', 'method', 'date_from', 'date_to', 'sort', 'direction', 'trashed']);

        return view('admin.donations.index', [
            'donations' => $this->donations->adminSearch($filters, 20),
            'campaigns' => $this->campaigns->allOrdered(),
            'statuses' => PaymentStatus::options(),
            'methods' => PaymentMethod::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Donation::class);

        return view('admin.donations.create', [
            'campaigns' => $this->campaigns->allOrdered(),
            'statuses' => PaymentStatus::options(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function store(StoreDonationRequest $request): RedirectResponse
    {
        $this->authorize('create', Donation::class);

        $donation = $this->donationService->createAdminDonation($request->validated());

        return redirect()->route('admin.donations.show', $donation)
            ->with('status', 'Donation recorded successfully.');
    }

    public function show(Donation $donation): View
    {
        $this->authorize('view', $donation);

        return view('admin.donations.show', [
            'donation' => $donation->load('campaign'),
        ]);
    }

    public function edit(Donation $donation): View
    {
        $this->authorize('update', $donation);

        return view('admin.donations.edit', [
            'donation' => $donation->load('campaign'),
            'campaigns' => $this->campaigns->allOrdered(),
            'statuses' => PaymentStatus::options(),
            'methods' => PaymentMethod::options(),
        ]);
    }

    public function update(UpdateDonationRequest $request, Donation $donation): RedirectResponse
    {
        $this->authorize('update', $donation);

        $this->donationService->updateDonation($donation, $request->validated());

        return redirect()->route('admin.donations.show', $donation)
            ->with('status', 'Donation updated successfully.');
    }

    public function destroy(Donation $donation): RedirectResponse
    {
        $this->authorize('delete', $donation);

        $this->donationService->deleteDonation($donation);

        return redirect()->route('admin.donations.index')
            ->with('status', 'Donation moved to trash.');
    }

    public function restore(Donation $donation): RedirectResponse
    {
        $this->authorize('restore', $donation);

        $this->donationService->restoreDonation($donation);

        return redirect()->route('admin.donations.index', ['trashed' => 1])
            ->with('status', 'Donation restored successfully.');
    }

    /**
     * Verify an offline (bank transfer / UPI) donation and trigger the
     * receipt + thank-you emails.
     */
    public function markCompleted(Request $request, Donation $donation): RedirectResponse
    {
        $this->authorize('update', $donation);

        $this->donationService->markCompleted($donation, $request->input('transaction_id'));

        return back()->with('status', "Donation marked as completed — receipt {$donation->fresh()->receipt_number} issued.");
    }

    public function markFailed(Donation $donation): RedirectResponse
    {
        $this->authorize('update', $donation);

        $this->donationService->markFailed($donation, 'Marked as failed from the admin panel.');

        return back()->with('status', 'Donation marked as failed.');
    }

    /**
     * Export the currently filtered donations as CSV or Excel (?format=xlsx).
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', Donation::class);

        $filters = $request->only(['q', 'campaign', 'status', 'method', 'date_from', 'date_to', 'trashed']);

        return $request->query('format') === 'xlsx'
            ? $this->exportService->downloadXlsx($filters)
            : $this->exportService->streamCsv($filters);
    }
}
