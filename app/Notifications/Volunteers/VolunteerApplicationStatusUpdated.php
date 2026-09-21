<?php

namespace App\Notifications\Volunteers;

use App\Enums\VolunteerApplicationStatus;
use App\Models\VolunteerApplication;
use App\Notifications\BaseNotification;
use Illuminate\Notifications\Messages\MailMessage;

/**
 * Applicant status update — approved, rejected, under review or on hold.
 * One class, one template; the copy switches on the status so the same
 * flow serves every reviewable outcome. Archiving is silent.
 */
class VolunteerApplicationStatusUpdated extends BaseNotification
{
    public function __construct(public VolunteerApplication $application)
    {
        parent::__construct();
    }

    /**
     * Statuses that are meaningful to the applicant.
     *
     * @return list<VolunteerApplicationStatus>
     */
    public static function notifiableStatuses(): array
    {
        return [
            VolunteerApplicationStatus::Approved,
            VolunteerApplicationStatus::Rejected,
            VolunteerApplicationStatus::UnderReview,
            VolunteerApplicationStatus::OnHold,
        ];
    }

    public function subjectLine(): string
    {
        return match ($this->application->status) {
            VolunteerApplicationStatus::Approved => 'Welcome Aboard! Your Volunteer Application Is Approved',
            VolunteerApplicationStatus::Rejected => 'An Update on Your Volunteer Application',
            VolunteerApplicationStatus::UnderReview => 'Your Volunteer Application Is Under Review',
            VolunteerApplicationStatus::OnHold => 'Your Volunteer Application Is On Hold',
            default => 'Your Volunteer Application Status',
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView($this->subjectLine(), 'emails.volunteers.status-update', [
            'application' => $this->application,
            'status' => $this->application->status,
        ]);
    }
}
