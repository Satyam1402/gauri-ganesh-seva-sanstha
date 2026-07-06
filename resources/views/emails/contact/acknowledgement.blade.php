@extends('emails.contact.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $enquiry->name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We have received your message and our team will get back to you as soon as
        possible — usually within <strong>2–3 working days</strong>.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Reference:</strong> {{ $enquiry->reference }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Subject:</strong> {{ $enquiry->subject }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Category:</strong> {{ $enquiry->category->label() }}</p>
                <p style="margin:0; font-size:13px;"><strong>Received:</strong> {{ $enquiry->created_at->format('d M Y, g:i A') }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6; color:#54615c;">
        <em>Your message:</em><br>
        {{ \Illuminate\Support\Str::limit($enquiry->message, 500) }}
    </p>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
