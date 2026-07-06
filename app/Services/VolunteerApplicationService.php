<?php

namespace App\Services;

use App\Enums\VolunteerApplicationStatus;
use App\Interfaces\VolunteerApplicationRepositoryInterface;
use App\Mail\NewVolunteerApplicationNotificationMail;
use App\Mail\VolunteerApplicationApprovedMail;
use App\Mail\VolunteerApplicationReceivedMail;
use App\Mail\VolunteerApplicationRejectedMail;
use App\Models\VolunteerApplication;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class VolunteerApplicationService
{
    public function __construct(
        private VolunteerApplicationRepositoryInterface $applications,
    ) {}

    /**
     * Store a public application with its uploads, then queue the applicant
     * confirmation and admin notification emails.
     *
     * @param  array<string, mixed>  $data  Validated form data.
     */
    public function submit(array $data): VolunteerApplication
    {
        $application = DB::transaction(function () use ($data) {
            /** @var VolunteerApplication $application */
            $application = $this->applications->create([
                ...collect($data)->except(['consent', 'profile_photo', 'identity_proof', 'resume'])->all(),
                'consented_at' => now(),
                'status' => VolunteerApplicationStatus::Pending->value,
            ]);

            foreach (['profile_photo', 'identity_proof', 'resume'] as $collection) {
                if (($data[$collection] ?? null) instanceof UploadedFile) {
                    $application->addMedia($data[$collection])->toMediaCollection($collection);
                }
            }

            return $application;
        });

        Mail::to($application->email)->queue(new VolunteerApplicationReceivedMail($application));
        $this->notifyAdmin($application);

        return $application;
    }

    /**
     * Update the review status and internal notes. A status change stamps the
     * reviewer and triggers the approval/rejection email where applicable.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(VolunteerApplication $application, array $data): VolunteerApplication
    {
        $newStatus = $data['status'] instanceof VolunteerApplicationStatus
            ? $data['status']
            : VolunteerApplicationStatus::from($data['status']);

        $statusChanged = $application->status !== $newStatus;

        $this->applications->update($application, [
            'status' => $newStatus->value,
            'admin_notes' => array_key_exists('admin_notes', $data)
                ? $data['admin_notes']
                : $application->admin_notes,
            ...($statusChanged ? [
                'reviewed_by' => auth()->id(),
                'reviewed_at' => now(),
            ] : []),
        ]);

        $application->refresh();

        if ($statusChanged) {
            $this->sendStatusMail($application);
        }

        return $application;
    }

    /**
     * Convenience wrapper for the single-purpose admin actions
     * (approve / reject / hold / archive).
     */
    public function changeStatus(VolunteerApplication $application, VolunteerApplicationStatus $status): VolunteerApplication
    {
        return $this->update($application, ['status' => $status]);
    }

    /**
     * Apply a status to many applications. Runs through changeStatus() so
     * each transition gets reviewer stamping and status emails.
     *
     * @param  list<int>  $ids
     * @return int Number of applications updated.
     */
    public function bulkUpdateStatus(array $ids, VolunteerApplicationStatus $status): int
    {
        $applications = $this->applications->findMany($ids);

        foreach ($applications as $application) {
            $this->changeStatus($application, $status);
        }

        return $applications->count();
    }

    /**
     * Soft-delete many applications.
     *
     * @param  list<int>  $ids
     * @return int Number of applications deleted.
     */
    public function bulkDelete(array $ids): int
    {
        $applications = $this->applications->findMany($ids);

        foreach ($applications as $application) {
            $this->applications->delete($application);
        }

        return $applications->count();
    }

    public function delete(VolunteerApplication $application): bool
    {
        return $this->applications->delete($application);
    }

    public function restore(VolunteerApplication $application): VolunteerApplication
    {
        $application->restore();

        return $application;
    }

    private function sendStatusMail(VolunteerApplication $application): void
    {
        match ($application->status) {
            VolunteerApplicationStatus::Approved => Mail::to($application->email)
                ->queue(new VolunteerApplicationApprovedMail($application)),
            VolunteerApplicationStatus::Rejected => Mail::to($application->email)
                ->queue(new VolunteerApplicationRejectedMail($application)),
            default => null,
        };
    }

    private function notifyAdmin(VolunteerApplication $application): void
    {
        $recipient = config('volunteers.admin_notification_email');

        if ($recipient) {
            Mail::to($recipient)->queue(new NewVolunteerApplicationNotificationMail($application));
        }
    }
}
