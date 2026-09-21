<?php

namespace App\Notifications\Contact;

use App\Models\ContactEnquiry;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Auto-acknowledgement to the person who sent a contact enquiry.
 */
class EnquiryAcknowledgement extends BaseNotification
{
    public function __construct(public ContactEnquiry $enquiry)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('We Received Your Message', 'emails.contact.acknowledgement', [
            'enquiry' => $this->enquiry,
        ]);
    }
}
