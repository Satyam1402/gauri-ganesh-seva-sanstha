<?php

namespace App\Mail;

use App\Models\VolunteerApplication;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class VolunteerApplicationRejectedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public VolunteerApplication $application) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'An Update on Your Volunteer Application — '.config('app.name'),
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.volunteers.application-rejected',
        );
    }
}
