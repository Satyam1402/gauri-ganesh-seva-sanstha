@extends('emails.layout')

@section('preheader', 'Your donation of '.format_inr((float) $donation->amount).' has been received. Receipt '.$donation->receipt_number.'.')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $donation->donor_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Your generous donation of <strong style="color:{{ $brand['primaryColor'] }};">{{ format_inr((float) $donation->amount) }}</strong>
        @if ($donation->campaign)
            toward <strong>{{ $donation->campaign->name }}</strong>
        @endif
        has been received. Because of supporters like you, we can continue our seva: distributing food,
        supporting education, organising medical camps, and standing beside communities when they need it most.
    </p>

    <p style="margin:0 0 12px; font-size:14px; font-weight:bold; color:#2b2620;">Donation Receipt</p>

    @include('emails.partials.details', ['rows' => [
        'Receipt Number' => $donation->receipt_number,
        'Donor Name' => $donation->donor_name,
        'PAN' => $donation->pan_number ? str_repeat('X', max(0, strlen($donation->pan_number) - 4)).substr($donation->pan_number, -4) : null,
        'Campaign' => $donation->campaign?->name ?? 'General Donation',
        'Amount' => format_inr((float) $donation->amount),
        'Payment Method' => $donation->payment_method->label(),
        'Transaction / Reference' => $donation->transaction_id,
        'Date' => $donation->donated_at?->format('d M Y, h:i A'),
    ]])

    <p style="margin:0 0 8px; font-size:13px; line-height:1.6; color:#8c8577;">
        Please keep this email for your records. If any detail is incorrect, reply through our contact page
        quoting the receipt number so we can issue a corrected receipt.
    </p>

    @include('emails.partials.cta', ['url' => route('donations.campaigns.index'), 'label' => 'See Our Campaigns'])

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
