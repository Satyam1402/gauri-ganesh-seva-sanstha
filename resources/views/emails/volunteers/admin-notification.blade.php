@extends('emails.volunteers.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Volunteer Application</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        A new volunteer application has been submitted on the website and is awaiting review.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Name:</strong> {{ $application->fullName() }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Email:</strong> {{ $application->email }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Phone:</strong> {{ $application->phone }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>City:</strong> {{ $application->city }}, {{ $application->state }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Occupation:</strong> {{ $application->occupation }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Areas of Interest:</strong> {{ implode(', ', $application->interestLabels()) }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Availability:</strong> {{ $application->availability->label() }}</p>
                <p style="margin:0; font-size:13px;"><strong>Submitted:</strong> {{ $application->created_at->format('d M Y, g:i A') }}</p>
            </td>
        </tr>
    </table>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('admin.volunteer-applications.show', $application) }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    Review Application
                </a>
            </td>
        </tr>
    </table>
@endsection
