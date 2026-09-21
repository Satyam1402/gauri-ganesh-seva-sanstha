<?php

namespace App\Notifications\Donations;

use App\Enums\NotificationCategory;
use App\Enums\PaymentStatus;
use App\Models\Donation;
use App\Notifications\AdminNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Internal alert for users who manage donations: a completed donation, or
 * an offline donation that now needs manual verification.
 */
class NewDonationAlert extends AdminNotification
{
    public function __construct(public Donation $donation)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Donation;
    }

    public function title(): string
    {
        $amount = format_inr((float) $this->donation->amount);

        return $this->donation->payment_status === PaymentStatus::Completed
            ? "Donation completed: {$amount} from {$this->donation->donor_name}"
            : "Donation awaiting verification: {$amount} from {$this->donation->donor_name}";
    }

    public function message(): string
    {
        $campaign = $this->donation->campaign?->name ?? 'General Donation';

        return "{$campaign} · {$this->donation->payment_method->label()} · {$this->donation->payment_status->label()}";
    }

    public function actionUrl(): ?string
    {
        return route('admin.donations.show', $this->donation);
    }

    public function toMail(object $notifiable): MailMessage
    {
        $subject = $this->donation->payment_status === PaymentStatus::Completed
            ? 'Donation Completed'
            : 'Donation Awaiting Verification';

        return $this->mailFromView($subject, 'emails.donations.admin-notification', [
            'donation' => $this->donation->loadMissing('campaign'),
        ]);
    }
}
