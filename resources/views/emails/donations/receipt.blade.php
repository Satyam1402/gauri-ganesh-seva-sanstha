@extends('emails.donations.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Donation Receipt</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Dear {{ $donation->donor_name }},
    </p>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        We gratefully acknowledge your donation to {{ config('app.name') }}. Please keep this receipt for your records.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5ded2; border-radius:6px; font-size:14px;">
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577; width:45%;">Receipt Number</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; font-weight:bold;">{{ $donation->receipt_number }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Donor Name</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->donor_name }}</td>
        </tr>
        @if ($donation->pan_number)
            <tr>
                <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">PAN</td>
                <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->pan_number }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Campaign</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->campaign?->name ?? 'General Donation' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Amount</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; font-weight:bold; color:#8a3324;">{{ format_inr((float) $donation->amount) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Payment Method</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->payment_method->label() }}</td>
        </tr>
        @if ($donation->transaction_id)
            <tr>
                <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Transaction ID</td>
                <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->transaction_id }}</td>
            </tr>
        @endif
        <tr>
            <td style="padding:10px 16px; color:#8c8577;">Date</td>
            <td style="padding:10px 16px;">{{ $donation->donated_at->format('d M Y, h:i A') }}</td>
        </tr>
    </table>

    <p style="margin:24px 0 0; font-size:13px; line-height:1.6; color:#8c8577;">
        If any detail on this receipt is incorrect, please contact us so we can issue a corrected receipt.
    </p>
@endsection
