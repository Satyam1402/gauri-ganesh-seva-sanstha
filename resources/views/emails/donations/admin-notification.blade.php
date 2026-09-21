@extends('emails.layout')

@php
    $completed = $donation->payment_status->value === 'completed';
@endphp

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">
        {{ $completed ? 'Donation Completed' : 'Donation Awaiting Verification' }}
    </h1>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        A donation has been {{ $completed ? 'completed and receipted.' : 'recorded with an offline payment method and needs to be verified once the funds arrive.' }}
    </p>

    @include('emails.partials.details', ['rows' => [
        'Donor' => $donation->donor_name.($donation->is_anonymous ? ' (anonymous on site)' : ''),
        'Email / Phone' => $donation->donor_email.($donation->donor_phone ? ' / '.$donation->donor_phone : ''),
        'Campaign' => $donation->campaign?->name ?? 'General Donation',
        'Amount' => format_inr((float) $donation->amount),
        'Method / Status' => $donation->payment_method->label().' — '.$donation->payment_status->label(),
        'Transaction ID' => $donation->transaction_id,
        'Receipt Number' => $donation->receipt_number,
        'Date' => $donation->donated_at?->format('d M Y, h:i A'),
    ]])

    @include('emails.partials.cta', ['url' => route('admin.donations.show', $donation), 'label' => 'View in Admin Panel', 'margin' => '0'])
@endsection
