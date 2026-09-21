@extends('emails.layout')

@section('preheader', 'Your donation attempt could not be completed. You can try again at any time.')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Your Donation Could Not Be Completed</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Dear {{ $donation->donor_name }},
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We were unable to complete your donation
        @if ($donation->campaign)
            toward <strong>{{ $donation->campaign->name }}</strong>
        @endif
        — the payment was not confirmed. No receipt has been issued for this attempt.
    </p>

    @include('emails.partials.details', ['rows' => [
        'Amount' => format_inr((float) $donation->amount),
        'Payment Method' => $donation->payment_method->label(),
        'Attempted On' => $donation->donated_at?->format('d M Y, h:i A'),
        'Reference' => $donation->reference,
    ]])

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        You are welcome to try again whenever convenient. If an amount was debited from your account for this
        attempt, please contact us quoting the reference above and we will look into it right away.
    </p>

    @include('emails.partials.cta', [
        'url' => $donation->campaign ? route('donations.donate', $donation->campaign) : route('donations.donate'),
        'label' => 'Try Again',
        'margin' => '0',
    ])

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        Thank you for your support,<br>
        <strong>The {{ $brand['name'] }} Team</strong>
    </p>
@endsection
