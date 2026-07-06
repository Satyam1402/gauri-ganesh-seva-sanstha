@extends('emails.volunteers.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $application->first_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We have received your volunteer application and our team is excited to learn
        more about you. Your application is currently <strong>pending review</strong> —
        we will contact you via <strong>{{ $application->preferred_communication_method->label() }}</strong>
        once it has been assessed.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Application Reference:</strong> {{ $application->reference }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Areas of Interest:</strong> {{ implode(', ', $application->interestLabels()) }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Availability:</strong> {{ $application->availability->label() }}</p>
                <p style="margin:0; font-size:13px;"><strong>Applied On:</strong> {{ $application->created_at->format('d M Y, g:i A') }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        <strong>What happens next?</strong><br>
        1. Our volunteer coordinator reviews your application.<br>
        2. We reach out for a short introductory conversation.<br>
        3. You receive an orientation and join your first seva activity.
    </p>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
