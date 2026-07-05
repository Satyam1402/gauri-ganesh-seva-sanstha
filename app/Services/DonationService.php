<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Interfaces\DonationRepositoryInterface;
use App\Mail\DonationReceiptMail;
use App\Mail\DonationThankYouMail;
use App\Mail\NewDonationNotificationMail;
use App\Models\Donation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class DonationService
{
    public function __construct(
        private DonationRepositoryInterface $donations,
        private DonationCampaignService $campaignService,
    ) {}

    /**
     * Create a pending donation from the public donate form and notify the
     * admin team. Payment (online or offline) happens after this step.
     *
     * @param  array<string, mixed>  $data
     */
    public function createPendingDonation(array $data): Donation
    {
        /** @var Donation $donation */
        $donation = $this->donations->create([
            'donation_campaign_id' => $data['donation_campaign_id'] ?? null,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'],
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_address' => $data['donor_address'] ?? null,
            'pan_number' => isset($data['pan_number']) ? strtoupper($data['pan_number']) : null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('donations.currency'),
            'payment_method' => $data['payment_method'],
            'payment_status' => PaymentStatus::Pending->value,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'donated_at' => now(),
            'remarks' => $data['remarks'] ?? null,
        ]);

        $this->notifyAdmin($donation);

        return $donation;
    }

    /**
     * Manual entry from the admin panel (cheques, cash-deposited transfers,
     * historical records). Completed entries get the full completion flow.
     *
     * @param  array<string, mixed>  $data
     */
    public function createAdminDonation(array $data): Donation
    {
        /** @var Donation $donation */
        $donation = $this->donations->create([
            'donation_campaign_id' => $data['donation_campaign_id'] ?? null,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'],
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_address' => $data['donor_address'] ?? null,
            'pan_number' => isset($data['pan_number']) ? strtoupper($data['pan_number']) : null,
            'amount' => $data['amount'],
            'currency' => $data['currency'] ?? config('donations.currency'),
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'payment_status' => PaymentStatus::Pending->value,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'donated_at' => $data['donated_at'] ?? now(),
            'remarks' => $data['remarks'] ?? null,
        ]);

        if (($data['payment_status'] ?? null) === PaymentStatus::Completed->value) {
            $donation = $this->markCompleted($donation, $data['transaction_id'] ?? null, (bool) ($data['send_emails'] ?? true));
        } elseif (! empty($data['payment_status']) && $data['payment_status'] !== PaymentStatus::Pending->value) {
            $donation->forceFill(['payment_status' => $data['payment_status']])->save();
        }

        return $donation;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateDonation(Donation $donation, array $data): Donation
    {
        $previousStatus = $donation->payment_status;

        $this->donations->update($donation, [
            'donation_campaign_id' => $data['donation_campaign_id'] ?? null,
            'donor_name' => $data['donor_name'],
            'donor_email' => $data['donor_email'],
            'donor_phone' => $data['donor_phone'] ?? null,
            'donor_address' => $data['donor_address'] ?? null,
            'pan_number' => isset($data['pan_number']) ? strtoupper($data['pan_number']) : null,
            'amount' => $data['amount'],
            'payment_method' => $data['payment_method'],
            'transaction_id' => $data['transaction_id'] ?? null,
            'is_anonymous' => (bool) ($data['is_anonymous'] ?? false),
            'donated_at' => $data['donated_at'] ?? $donation->donated_at,
            'remarks' => $data['remarks'] ?? null,
        ]);

        $newStatus = PaymentStatus::from($data['payment_status']);

        if ($newStatus !== $previousStatus) {
            return $this->transitionStatus($donation, $newStatus, sendEmails: (bool) ($data['send_emails'] ?? true));
        }

        $this->refreshCampaignTotals($donation);

        return $donation->refresh();
    }

    /**
     * Move a donation to a new payment status, running the completion
     * side-effects (receipt number, campaign totals, emails) when needed.
     */
    public function transitionStatus(Donation $donation, PaymentStatus $status, bool $sendEmails = true): Donation
    {
        if ($status === PaymentStatus::Completed) {
            return $this->markCompleted($donation, $donation->transaction_id, $sendEmails);
        }

        $donation->forceFill(['payment_status' => $status->value])->save();
        $this->refreshCampaignTotals($donation);

        return $donation->refresh();
    }

    /**
     * Completion is the only transition with side-effects: assign the
     * receipt number, update the campaign's raised total, email the donor.
     */
    public function markCompleted(Donation $donation, ?string $transactionId = null, bool $sendEmails = true): Donation
    {
        if ($donation->isCompleted()) {
            return $donation;
        }

        DB::transaction(function () use ($donation, $transactionId): void {
            $donation->forceFill([
                'payment_status' => PaymentStatus::Completed->value,
                'transaction_id' => $transactionId ?? $donation->transaction_id,
            ])->save();

            if (! $donation->receipt_number) {
                $donation->forceFill([
                    'receipt_number' => $this->generateReceiptNumber($donation),
                ])->save();
            }
        });

        $this->refreshCampaignTotals($donation);

        if ($sendEmails) {
            Mail::to($donation->donor_email)->queue(new DonationReceiptMail($donation));
            Mail::to($donation->donor_email)->queue(new DonationThankYouMail($donation));
            $this->notifyAdmin($donation->refresh());
        }

        return $donation->refresh();
    }

    public function markFailed(Donation $donation, ?string $reason = null): Donation
    {
        if ($reason) {
            $donation->mergeMeta(['failure_reason' => $reason]);
        }

        $donation->forceFill(['payment_status' => PaymentStatus::Failed->value])->save();

        return $donation->refresh();
    }

    public function deleteDonation(Donation $donation): bool
    {
        $deleted = (bool) $donation->delete();
        $this->refreshCampaignTotals($donation);

        return $deleted;
    }

    public function restoreDonation(Donation $donation): Donation
    {
        $donation->restore();
        $this->refreshCampaignTotals($donation);

        return $donation;
    }

    /**
     * Receipt numbers are sequential per donation id and only exist for
     * completed donations: e.g. GGSS-2026-000042.
     */
    private function generateReceiptNumber(Donation $donation): string
    {
        return sprintf(
            '%s-%s-%06d',
            config('donations.receipt_prefix'),
            $donation->donated_at?->format('Y') ?? now()->format('Y'),
            $donation->id,
        );
    }

    private function refreshCampaignTotals(Donation $donation): void
    {
        if ($donation->campaign) {
            $this->campaignService->recalculateRaised($donation->campaign);
        }
    }

    private function notifyAdmin(Donation $donation): void
    {
        $recipient = config('donations.admin_notification_email');

        if ($recipient) {
            Mail::to($recipient)->queue(new NewDonationNotificationMail($donation));
        }
    }

    /**
     * Offline payment methods currently enabled — used by the public flow to
     * decide whether a donation goes straight to the instructions page.
     */
    public function isOfflineMethod(string $method): bool
    {
        $case = PaymentMethod::tryFrom($method);

        return $case !== null && $case->isOffline();
    }
}
