<?php

namespace App\Notifications\Volunteers;

use App\Enums\NotificationCategory;
use App\Models\VolunteerApplication;
use App\Notifications\AdminNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Internal alert for users who manage volunteers.
 */
class NewVolunteerApplicationAlert extends AdminNotification
{
    public function __construct(public VolunteerApplication $application)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Volunteer;
    }

    public function title(): string
    {
        return 'New volunteer application from '.$this->application->fullName();
    }

    public function message(): string
    {
        $location = collect([$this->application->city, $this->application->state])->filter()->implode(', ');

        return collect([
            implode(', ', $this->application->interestLabels()),
            $location,
        ])->filter()->implode(' · ');
    }

    public function actionUrl(): ?string
    {
        return route('admin.volunteer-applications.show', $this->application);
    }

    public function actionLabel(): string
    {
        return 'Review Application';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('New Volunteer Application: '.$this->application->fullName(), 'emails.volunteers.admin-notification', [
            'application' => $this->application,
        ]);
    }
}
