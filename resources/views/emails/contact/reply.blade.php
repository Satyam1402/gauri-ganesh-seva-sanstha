@extends('emails.contact.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Hello {{ $enquiry->name }},</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Thank you for contacting {{ config('app.name') }}. Here is our response to your enquiry
        <strong>&ldquo;{{ $enquiry->subject }}&rdquo;</strong>:
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border-left:4px solid #8a3324; background-color:#faf7f1; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0; font-size:14px; line-height:1.7; white-space:pre-line;">{{ $reply->message }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#8c8577;">
        <em>Your original message ({{ $enquiry->created_at->format('d M Y') }}):</em><br>
        {{ \Illuminate\Support\Str::limit($enquiry->message, 400) }}
    </p>

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#8c8577;">
        Reference: {{ $enquiry->reference }}
    </p>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        Warm regards,<br>
        <strong>{{ $reply->author?->name ?? 'The '.config('app.name').' Team' }}</strong><br>
        {{ config('app.name') }}
    </p>
@endsection
