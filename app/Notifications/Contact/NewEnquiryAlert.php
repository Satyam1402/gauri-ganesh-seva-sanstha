<?php

namespace App\Notifications\Contact;

use App\Enums\NotificationCategory;
use App\Models\ContactEnquiry;
use App\Notifications\AdminNotification;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Str;

/**
 * Internal alert for users who manage contact messages.
 */
class NewEnquiryAlert extends AdminNotification
{
    public function __construct(public ContactEnquiry $enquiry)
    {
        parent::__construct();
    }

    public static function category(): NotificationCategory
    {
        return NotificationCategory::Enquiry;
    }

    public function title(): string
    {
        return "New enquiry from {$this->enquiry->name}: ".Str::limit($this->enquiry->subject, 80);
    }

    public function message(): string
    {
        return $this->enquiry->category->label().' · '.Str::limit($this->enquiry->message, 120);
    }

    public function actionUrl(): ?string
    {
        return route('admin.contact-enquiries.show', $this->enquiry);
    }

    public function actionLabel(): string
    {
        return 'View & Reply';
    }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailFromView('New Enquiry ['.$this->enquiry->category->label().']: '.$this->enquiry->subject, 'emails.contact.admin-notification', [
            'enquiry' => $this->enquiry,
        ]);
    }
}
