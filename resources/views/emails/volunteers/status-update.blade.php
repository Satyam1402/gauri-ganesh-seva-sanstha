@extends('emails.layout')

{{-- $status is App\Enums\VolunteerApplicationStatus; only approved / rejected / under_review / on_hold reach this template. --}}

@section('preheader', 'Your volunteer application is now '.strtolower($status->label()).' (ref. '.$application->reference.').')

@section('body')
    @switch($status)
        @case(\App\Enums\VolunteerApplicationStatus::Approved)
            <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Welcome to the Family, {{ $application->first_name }}!</h1>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                Wonderful news — your volunteer application has been
                <strong style="color:#2e7d32;">approved</strong>. We are delighted to have you
                join us in serving the community.
            </p>
            @break

        @case(\App\Enums\VolunteerApplicationStatus::Rejected)
            <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You for Your Interest, {{ $application->first_name }}</h1>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                Thank you for taking the time to apply as a volunteer with {{ $brand['name'] }}.
                After careful review, we are unable to move forward with your application at this time.
            </p>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                This is often simply a matter of current capacity or a mismatch with the roles we need
                filled right now — it is not a reflection of your worth or willingness to serve. We warmly
                encourage you to apply again in the future.
            </p>
            @break

        @case(\App\Enums\VolunteerApplicationStatus::UnderReview)
            <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Your Application Is Under Review, {{ $application->first_name }}</h1>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                Good news — our volunteer coordinator has picked up your application and is reviewing it now.
                We may reach out via <strong>{{ $application->preferred_communication_method->label() }}</strong>
                for a short introductory conversation.
            </p>
            @break

        @case(\App\Enums\VolunteerApplicationStatus::OnHold)
            <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Your Application Is On Hold, {{ $application->first_name }}</h1>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                Your volunteer application is currently on hold. This usually means we do not have a matching
                opportunity open right now — nothing more is needed from you, and we will be in touch as soon
                as a suitable role comes up.
            </p>
            @break

        @default
            <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">An Update on Your Volunteer Application</h1>
            <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
                The status of your volunteer application has changed to <strong>{{ $status->label() }}</strong>.
            </p>
    @endswitch

    @include('emails.partials.details', ['rows' => [
        'Application Reference' => $application->reference,
        'Status' => $status->label(),
        'Areas of Interest' => implode(', ', $application->interestLabels()),
    ]])

    @if ($status === \App\Enums\VolunteerApplicationStatus::Approved)
        <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
            Our volunteer coordinator will contact you shortly via
            <strong>{{ $application->preferred_communication_method->label() }}</strong>
            with your orientation details and upcoming activities that match your interests.
        </p>
        @include('emails.partials.cta', ['url' => route('events.index'), 'label' => 'See Upcoming Events', 'margin' => '0'])
    @elseif ($status === \App\Enums\VolunteerApplicationStatus::Rejected)
        <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
            In the meantime, you can continue supporting our mission by attending our events,
            spreading the word, or contributing to our campaigns.
        </p>
        @include('emails.partials.cta', ['url' => route('home'), 'label' => 'Explore Other Ways to Help', 'margin' => '0'])
    @else
        @include('emails.partials.cta', ['url' => route('volunteer.create'), 'label' => 'About Volunteering', 'margin' => '0'])
    @endif

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
