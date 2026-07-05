@extends('emails.events.layout')

@php
    $event = $registration->event;
@endphp

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">You're Registered, {{ $registration->name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Thank you for registering for <strong style="color:#8a3324;">{{ $event->title }}</strong>.
        Your registration has been received and is currently <strong>pending confirmation</strong> —
        we will be in touch if anything else is needed.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Date:</strong> {{ $event->dateRange() }}</p>
                @if ($event->timeRange())
                    <p style="margin:0 0 8px; font-size:13px;"><strong>Time:</strong> {{ $event->timeRange() }}</p>
                @endif
                @if ($event->locationLine())
                    <p style="margin:0 0 8px; font-size:13px;"><strong>Venue:</strong> {{ $event->locationLine() }}</p>
                @endif
                @if ($event->address)
                    <p style="margin:0 0 8px; font-size:13px;"><strong>Address:</strong> {{ $event->address }}</p>
                @endif
                @if ($event->organizer)
                    <p style="margin:0; font-size:13px;"><strong>Organizer:</strong> {{ $event->organizer }}</p>
                @endif
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('events.show', $event) }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    View Event Details
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        We look forward to seeing you there!<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
