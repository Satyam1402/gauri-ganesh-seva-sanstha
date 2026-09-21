@extends('emails.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Event Registration</h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        A new registration has been submitted for <strong style="color:{{ $brand['primaryColor'] }};">{{ $event->title }}</strong>
        ({{ $event->dateRange() }}{{ $event->timeRange() ? ', '.$event->timeRange() : '' }}{{ $event->locationLine() ? ' · '.$event->locationLine() : '' }}).
    </p>

    @include('emails.partials.details', ['rows' => [
        'Registration Number' => $registration->registrationNumber(),
        'Name' => $registration->name,
        'Email' => $registration->email,
        'Phone' => $registration->phone,
        'City' => $registration->city,
        'Message' => $registration->message,
        'Registered At' => $registration->created_at->format('d M Y, g:i A'),
    ]])

    @include('emails.partials.cta', ['url' => route('admin.event-registrations.index', ['event' => $event->id]), 'label' => 'Manage Registrations', 'margin' => '0'])
@endsection
