<?php

namespace App\Notifications\Donations;

use App\Models\Donation;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Sent to the donor when a payment attempt is known to have failed —
 * only after the gateway callback or an admin has confirmed it, never on
 * a mere timeout, so we never tell a donor their money was not taken
 * unless we know it.
 */
class DonationFailed extends BaseNotification
{
    public function __construct(public Donation $donation)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('Your Donation Could Not Be Completed', 'emails.donations.failed', [
            'donation' => $this->donation->loadMissing('campaign'),
        ]);
    }
}
