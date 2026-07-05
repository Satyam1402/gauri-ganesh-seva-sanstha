<?php

namespace App\Mail;

use App\Models\Donation;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class NewDonationNotificationMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(public Donation $donation) {}

    public function envelope(): Envelope
    {
        $status = $this->donation->payment_status->label();

        return new Envelope(
            subject: "[{$status}] Donation of ".format_inr((float) $this->donation->amount).' from '.$this->donation->donor_name,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.donations.admin-notification',
        );
    }
}
