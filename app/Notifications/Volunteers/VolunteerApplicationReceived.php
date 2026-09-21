<?php

namespace App\Notifications\Volunteers;

use App\Models\VolunteerApplication;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Applicant confirmation: the application was received and is pending
 * review. Carries the public reference only — never the internal notes.
 */
class VolunteerApplicationReceived extends BaseNotification
{
    public function __construct(public VolunteerApplication $application)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('We Received Your Volunteer Application', 'emails.volunteers.application-received', [
            'application' => $this->application,
        ]);
    }
}
