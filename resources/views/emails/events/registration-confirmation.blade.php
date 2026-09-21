@extends('emails.layout')

@section('preheader', 'Your registration for '.$event->title.' on '.$event->dateRange().' has been received.')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">You're Registered, {{ $registration->name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Thank you for registering for <strong style="color:{{ $brand['primaryColor'] }};">{{ $event->title }}</strong>.
        Your registration has been received and is currently <strong>pending confirmation</strong> —
        we will be in touch if anything else is needed.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Registration Number' => $registration->registrationNumber(),
        'Event' => $event->title,
        'Date' => $event->dateRange(),
        'Time' => $event->timeRange(),
        'Venue' => $event->locationLine(),
        'Address' => $event->address,
        'Organizer' => $event->organizer,
    ]])

    @include('emails.partials.cta', ['url' => route('events.show', $event), 'label' => 'View Event Details', 'margin' => '0'])

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        We look forward to seeing you there!<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
