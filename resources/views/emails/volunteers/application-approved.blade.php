@extends('emails.volunteers.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Welcome to the Family, {{ $application->first_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Wonderful news — your volunteer application has been
        <strong style="color:#2e7d32;">approved</strong>. We are delighted to have you
        join us in serving the community.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Application Reference:</strong> {{ $application->reference }}</p>
                <p style="margin:0; font-size:13px;"><strong>Areas of Interest:</strong> {{ implode(', ', $application->interestLabels()) }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        Our volunteer coordinator will contact you shortly via
        <strong>{{ $application->preferred_communication_method->label() }}</strong>
        with your orientation details and upcoming activities that match your interests.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('home') }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    Visit Our Website
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        Together, we restore dignity.<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
