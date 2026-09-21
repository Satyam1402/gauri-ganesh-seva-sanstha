<?php

namespace App\Notifications\Contact;

use App\Models\ContactEnquiry;
use App\Models\ContactEnquiryReply;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * A staff reply delivered to the enquirer. Only the reply text and the
 * original message are included — internal notes and assignee never are.
 */
class EnquiryReply extends BaseNotification
{
    public function __construct(
        public ContactEnquiry $enquiry,
        public ContactEnquiryReply $reply,
    ) {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('Re: '.$this->enquiry->subject, 'emails.contact.reply', [
            'enquiry' => $this->enquiry,
            'reply' => $this->reply->loadMissing('author'),
        ]);
    }
}
