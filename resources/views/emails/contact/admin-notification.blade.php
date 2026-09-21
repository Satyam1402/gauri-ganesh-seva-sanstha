@extends('emails.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">New Contact Enquiry</h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        A new enquiry has been submitted through the website contact form.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Name' => $enquiry->name,
        'Email' => $enquiry->email,
        'Phone' => $enquiry->phone,
        'Category' => $enquiry->category->label(),
        'Subject' => $enquiry->subject,
        'Reference' => $enquiry->reference,
        'Received' => $enquiry->created_at->format('d M Y, g:i A'),
    ]])

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6; color:#54615c; white-space:pre-line;">{{ \Illuminate\Support\Str::limit($enquiry->message, 800) }}</p>

    @include('emails.partials.cta', ['url' => route('admin.contact-enquiries.show', $enquiry), 'label' => 'View & Reply', 'margin' => '0'])
@endsection
