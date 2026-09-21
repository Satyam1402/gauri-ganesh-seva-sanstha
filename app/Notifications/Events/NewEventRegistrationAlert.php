<?php

namespace App\Notifications\Events;

use App\Enums\NotificationCategory;
use App\Models\EventRegistration;
use App\Notifications\AdminNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Internal alert for users who manage events.
 */
class NewEventRegistrationAlert extends AdminNotification
{
    public function __construct(public EventRegistration $registration)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Event;
    }

    public function title(): string
    {
        return "New registration: {$this->registration->name} — {$this->registration->event->title}";
    }

    public function message(): string
    {
        return $this->registration->event->dateRange()
            .($this->registration->city ? ' · '.$this->registration->city : '');
    }

    public function actionUrl(): ?string
    {
        return route('admin.event-registrations.index', ['event' => $this->registration->event_id]);
    }

    public function actionLabel(): string
    {
        return 'Manage Registrations';
    }

    public function toMail(object $notifiable): MailMessage
    {
        $registration = $this->registration->loadMissing('event');

        return $this->mailFromView('New Registration: '.$registration->event->title, 'emails.events.admin-notification', [
            'registration' => $registration,
            'event' => $registration->event,
        ]);
    }
}
