@extends('emails.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Volunteer Application</h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        A new volunteer application has been submitted on the website and is awaiting review.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Name' => $application->fullName(),
        'Email' => $application->email,
        'Phone' => $application->phone,
        'Location' => collect([$application->city, $application->state])->filter()->implode(', '),
        'Occupation' => $application->occupation,
        'Areas of Interest' => implode(', ', $application->interestLabels()),
        'Availability' => $application->availability->label(),
        'Reference' => $application->reference,
        'Submitted' => $application->created_at->format('d M Y, g:i A'),
    ]])

    @include('emails.partials.cta', ['url' => route('admin.volunteer-applications.show', $application), 'label' => 'Review Application', 'margin' => '0'])
@endsection
