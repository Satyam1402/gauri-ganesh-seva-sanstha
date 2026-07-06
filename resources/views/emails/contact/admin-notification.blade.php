@extends('emails.contact.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Contact Enquiry</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        A new enquiry has been submitted through the website contact form.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin:0 0 24px; border:1px solid #e5ded2; border-radius:6px;">
        <tr>
            <td style="padding:16px 20px;">
                <p style="margin:0 0 8px; font-size:13px;"><strong>Name:</strong> {{ $enquiry->name }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Email:</strong> {{ $enquiry->email }}</p>
                @if ($enquiry->phone)
                    <p style="margin:0 0 8px; font-size:13px;"><strong>Phone:</strong> {{ $enquiry->phone }}</p>
                @endif
                <p style="margin:0 0 8px; font-size:13px;"><strong>Category:</strong> {{ $enquiry->category->label() }}</p>
                <p style="margin:0 0 8px; font-size:13px;"><strong>Subject:</strong> {{ $enquiry->subject }}</p>
                <p style="margin:0; font-size:13px;"><strong>Received:</strong> {{ $enquiry->created_at->format('d M Y, g:i A') }}</p>
            </td>
        </tr>
    </table>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#54615c;">
        {{ \Illuminate\Support\Str::limit($enquiry->message, 800) }}
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('admin.contact-enquiries.show', $enquiry) }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    View & Reply
                </a>
            </td>
        </tr>
    </table>
@endsection
