@extends('emails.layout')

@section('preheader', 'We have received your volunteer application (ref. '.$application->reference.').')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $application->first_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We have received your volunteer application and our team is excited to learn
        more about you. Your application is currently <strong>pending review</strong> —
        we will contact you via <strong>{{ $application->preferred_communication_method->label() }}</strong>
        once it has been assessed.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Application Reference' => $application->reference,
        'Areas of Interest' => implode(', ', $application->interestLabels()),
        'Availability' => $application->availability->label(),
        'Applied On' => $application->created_at->format('d M Y, g:i A'),
    ]])

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        <strong>What happens next?</strong><br>
        1. Our volunteer coordinator reviews your application.<br>
        2. We reach out for a short introductory conversation.<br>
        3. You receive an orientation and join your first seva activity.
    </p>

    @include('emails.partials.cta', ['url' => route('activities.index'), 'label' => 'Explore Our Activities', 'margin' => '0'])

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
