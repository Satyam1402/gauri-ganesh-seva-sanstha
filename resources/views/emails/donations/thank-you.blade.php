@extends('emails.donations.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You, {{ $donation->donor_name }}!</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Your generous donation of <strong style="color:#8a3324;">{{ format_inr((float) $donation->amount) }}</strong>
        @if ($donation->campaign)
            toward <strong>{{ $donation->campaign->name }}</strong>
        @endif
        means the world to us — and to the families we serve.
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Because of supporters like you, we can continue our seva: distributing food, supporting education,
        organising medical camps, and standing beside communities when they need it most.
    </p>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        Your official receipt ({{ $donation->receipt_number }}) has been sent in a separate email.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('donations.campaigns.index') }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    See Our Campaigns
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
