@extends('emails.donations.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">
        {{ $donation->payment_status->value === 'completed' ? 'Donation Completed' : 'New Donation Received' }}
    </h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        A donation has been {{ $donation->payment_status->value === 'completed' ? 'completed' : 'recorded and is awaiting payment/verification' }}.
    </p>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border:1px solid #e5ded2; border-radius:6px; font-size:14px;">
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577; width:45%;">Donor</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->donor_name }}{{ $donation->is_anonymous ? ' (anonymous on site)' : '' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Email / Phone</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->donor_email }}{{ $donation->donor_phone ? ' / '.$donation->donor_phone : '' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Campaign</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->campaign?->name ?? 'General Donation' }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Amount</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; font-weight:bold; color:#8a3324;">{{ format_inr((float) $donation->amount) }}</td>
        </tr>
        <tr>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2; color:#8c8577;">Method / Status</td>
            <td style="padding:10px 16px; border-bottom:1px solid #e5ded2;">{{ $donation->payment_method->label() }} — {{ $donation->payment_status->label() }}</td>
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

    <table role="presentation" cellpadding="0" cellspacing="0" style="margin-top:24px;">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('admin.donations.show', $donation) }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    View in Admin Panel
                </a>
            </td>
        </tr>
    </table>
@endsection
