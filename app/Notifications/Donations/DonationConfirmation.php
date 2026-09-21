<?php

namespace App\Notifications\Donations;

use App\Models\Donation;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to the donor once the gateway (or an admin, for offline payments)
 * has confirmed the payment. Combines the thank-you note with the receipt
 * details — amount, campaign, reference, date and receipt number.
 */
class DonationConfirmation extends BaseNotification
{
    public function __construct(public Donation $donation)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('Thank You for Your Donation', 'emails.donations.confirmation', [
            'donation' => $this->donation->loadMissing('campaign'),
        ]);
    }
}
