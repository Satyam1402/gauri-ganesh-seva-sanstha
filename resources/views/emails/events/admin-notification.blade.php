@extends('emails.events.layout')

@php
    $event = $registration->event;
@endphp

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Event Registration</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        A new registration has been submitted for <strong style="color:#8a3324;">{{ $event->title }}</strong>
        ({{ $event->dateRange() }}).
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Name:</strong> {{ $registration->name }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Email:</strong> {{ $registration->email }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Phone:</strong> {{ $registration->phone }}</p>
                @if ($registration->city)
                    <p style="margin:0 0 8px; font-size:13px;"><strong>City:</strong> {{ $registration->city }}</p>
                @endif
                @if ($registration->message)
                    <p style="margin:0 0 8px; font-size:13px;"><strong>Message:</strong> {{ $registration->message }}</p>
                @endif
                <p style="margin:0; font-size:13px;"><strong>Registered At:</strong> {{ $registration->created_at->format('d M Y, g:i A') }}</p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('admin.event-registrations.index', ['event' => $event->id]) }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    Manage Registrations
                </a>
            </td>
        </tr>
    </table>
@endsection
