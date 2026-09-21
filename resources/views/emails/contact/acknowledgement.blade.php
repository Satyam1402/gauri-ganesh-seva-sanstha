@extends('emails.layout')

@section('preheader', 'We received your message “'.$enquiry->subject.'” and will reply soon.')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $enquiry->name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We have received your message and our team will get back to you as soon as
        possible — usually within <strong>2–3 working days</strong>.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Reference' => $enquiry->reference,
        'Subject' => $enquiry->subject,
        'Category' => $enquiry->category->label(),
        'Received' => $enquiry->created_at->format('d M Y, g:i A'),
    ]])

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#54615c;">
        <em>Your message:</em><br>
        {{ \Illuminate\Support\Str::limit($enquiry->message, 500) }}
    </p>

    @include('emails.partials.cta', ['url' => route('faq.index'), 'label' => 'Browse Frequently Asked Questions', 'margin' => '0'])

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
