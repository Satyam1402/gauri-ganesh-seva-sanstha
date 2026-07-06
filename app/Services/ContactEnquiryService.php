<?php

namespace App\Services;

use App\Enums\EnquiryStatus;
use App\Interfaces\ContactEnquiryRepositoryInterface;
use App\Mail\EnquiryAcknowledgementMail;
use App\Mail\EnquiryReplyMail;
use App\Mail\NewEnquiryNotificationMail;
use App\Models\ContactEnquiry;
use App\Models\ContactEnquiryReply;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

class ContactEnquiryService
{
    public function __construct(
        private ContactEnquiryRepositoryInterface $enquiries,
    ) {}

    /**
     * Store a public enquiry with its optional attachment, then queue the
     * acknowledgement and admin notification emails.
     *
     * @param  array<string, mixed>  $data  Validated form data (plus ip_address).
     */
    public function submit(array $data): ContactEnquiry
    {
        $enquiry = DB::transaction(function () use ($data) {
            /** @var ContactEnquiry $enquiry */
            $enquiry = $this->enquiries->create([
                ...collect($data)->except(['consent', 'attachment', 'g-recaptcha-response'])->all(),
                'consented_at' => now(),
                'status' => EnquiryStatus::New->value,
            ]);

            if (($data['attachment'] ?? null) instanceof UploadedFile) {
                $enquiry->addMedia($data['attachment'])->toMediaCollection('attachment');
            }

            return $enquiry;
        });

        Mail::to($enquiry->email)->queue(new EnquiryAcknowledgementMail($enquiry));
        $this->notifyAdmin($enquiry);

        return $enquiry;
    }

    /**
     * Update triage fields (status, internal notes, assignee).
     *
     * @param  array<string, mixed>  $data
     */
    public function update(ContactEnquiry $enquiry, array $data): ContactEnquiry
    {
        return $this->enquiries->update($enquiry, [
            'status' => $data['status'] instanceof EnquiryStatus ? $data['status']->value : $data['status'],
            'admin_notes' => array_key_exists('admin_notes', $data) ? $data['admin_notes'] : $enquiry->admin_notes,
            'assigned_to' => array_key_exists('assigned_to', $data) ? $data['assigned_to'] : $enquiry->assigned_to,
        ]);
    }

    /**
     * Record a staff reply, email it to the enquirer, and move a fresh
     * enquiry into "In Progress" so the pipeline reflects reality.
     */
    public function reply(ContactEnquiry $enquiry, User $author, string $message): ContactEnquiryReply
    {
        $reply = DB::transaction(function () use ($enquiry, $author, $message) {
            $reply = $enquiry->replies()->create([
                'user_id' => $author->id,
                'message' => $message,
            ]);

            $enquiry->update([
                'replied_at' => now(),
                'status' => $enquiry->status === EnquiryStatus::New
                    ? EnquiryStatus::InProgress->value
                    : $enquiry->status->value,
            ]);

            return $reply;
        });

        Mail::to($enquiry->email)->queue(new EnquiryReplyMail($enquiry, $reply));

        return $reply;
    }

    /**
     * @param  list<int>  $ids
     * @return int Number of enquiries updated.
     */
    public function bulkUpdateStatus(array $ids, EnquiryStatus $status): int
    {
        return $this->enquiries->bulkUpdateStatus($ids, $status);
    }

    /**
     * Soft-delete many enquiries.
     *
     * @param  list<int>  $ids
     * @return int Number of enquiries deleted.
     */
    public function bulkDelete(array $ids): int
    {
        $enquiries = $this->enquiries->findMany($ids);

        foreach ($enquiries as $enquiry) {
            $this->enquiries->delete($enquiry);
        }

        return $enquiries->count();
    }

    public function delete(ContactEnquiry $enquiry): bool
    {
        return $this->enquiries->delete($enquiry);
    }

    public function restore(ContactEnquiry $enquiry): ContactEnquiry
    {
        $enquiry->restore();

        return $enquiry;
    }

    private function notifyAdmin(ContactEnquiry $enquiry): void
    {
        $recipient = config('contact.admin_notification_email');

        if ($recipient) {
            Mail::to($recipient)->queue(new NewEnquiryNotificationMail($enquiry));
        }
    }
}
