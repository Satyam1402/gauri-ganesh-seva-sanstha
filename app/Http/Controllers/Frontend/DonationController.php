<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\PaymentStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Frontend\StorePublicDonationRequest;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Services\DonationService;
use App\Services\Payments\PaymentGatewayManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Throwable;

class DonationController extends Controller
{
    public function __construct(
        private DonationCampaignRepositoryInterface $campaigns,
        private DonationService $donationService,
        private PaymentGatewayManager $gateways,
    ) {}

    /**
     * The donate form — optionally pre-selected for a campaign.
     */
    public function create(Request $request, ?DonationCampaign $campaign = null): View
    {
        if ($campaign && ! $campaign->isAcceptingDonations()) {
            abort(404);
        }

        return view('frontend.donations.donate', [
            'campaign' => $campaign,
            'campaigns' => $this->campaigns->activeOrdered(),
            'gatewayOptions' => $this->gateways->enabledOptions(),
            'suggestedAmounts' => config('donations.suggested_amounts'),
        ]);
    }

    public function store(StorePublicDonationRequest $request): RedirectResponse
    {
        $donation = $this->donationService->createPendingDonation($request->validated());

        return redirect()->route('donations.pay', $donation);
    }

    /**
     * Kick off payment for a pending donation: renders offline instructions
     * or the gateway checkout, or redirects to a hosted payment page.
     */
    public function pay(Donation $donation): View|RedirectResponse
    {
        if ($donation->payment_status !== PaymentStatus::Pending) {
            return redirect()->route(
                $donation->isCompleted() ? 'donations.success' : 'donations.failed',
                $donation,
            );
        }

        try {
            $initiation = $this->gateways->gateway($donation->payment_method->value)->initiate($donation);
        } catch (Throwable $e) {
            Log::error('Donation payment initiation failed.', [
                'donation_id' => $donation->id,
                'gateway' => $donation->payment_method->value,
                'error' => $e->getMessage(),
            ]);

            $this->donationService->markFailed($donation, 'Payment initiation failed.');

            return redirect()->route('donations.failed', $donation);
        }

        return $initiation->isRedirect()
            ? redirect()->away($initiation->redirectUrl)
            : view($initiation->view, $initiation->data);
    }

    /**
     * Generic gateway callback — the gateway driver decides what the payload
     * means, keeping this flow identical for every online gateway.
     */
    public function callback(Request $request, Donation $donation, string $gateway): RedirectResponse
    {
        abort_unless($donation->payment_method->value === $gateway, 404);

        if ($donation->payment_status !== PaymentStatus::Pending) {
            return redirect()->route(
                $donation->isCompleted() ? 'donations.success' : 'donations.failed',
                $donation,
            );
        }

        $status = $this->gateways->gateway($gateway)->handleCallback($donation, $request->all());

        if ($status === PaymentStatus::Completed) {
            $this->donationService->markCompleted($donation, $donation->transaction_id);

            return redirect()->route('donations.success', $donation);
        }

        $this->donationService->markFailed($donation, 'Gateway callback reported failure.');

        return redirect()->route('donations.failed', $donation);
    }

    public function success(Donation $donation): View
    {
        return view('frontend.donations.success', [
            'donation' => $donation->load('campaign'),
        ]);
    }

    public function failed(Donation $donation): View
    {
        return view('frontend.donations.failed', [
            'donation' => $donation->load('campaign'),
        ]);
    }
}
