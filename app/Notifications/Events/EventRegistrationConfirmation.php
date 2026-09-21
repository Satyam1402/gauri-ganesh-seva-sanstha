<?php

namespace App\Notifications\Events;

use App\Models\EventRegistration;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Participant confirmation with the event's name, date, time, venue and
 * the registration number.
 */
class EventRegistrationConfirmation extends BaseNotification
{
    public function __construct(public EventRegistration $registration)
    {
        parent::__construct();
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->registration->loadMissing('event');

        return $this->mailFromView('Registration Received: '.$registration->event->title, 'emails.events.registration-confirmation', [
            'registration' => $registration,
            'event' => $registration->event,
        ]);
    }
}
