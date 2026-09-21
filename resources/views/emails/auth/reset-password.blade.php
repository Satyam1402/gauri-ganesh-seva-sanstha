@extends('emails.layout')

@section('preheader', 'Use the link inside to choose a new password. It expires in '.$expires.' minutes.')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Reset Your Password</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        We received a request to reset the password for your {{ $brand['name'] }} admin account.
        Click the button below to choose a new password.
    </p>

    @include('emails.partials.cta', ['url' => $url, 'label' => 'Reset Password', 'margin' => '0 0 24px'])

    <p style="margin:0 0 16px; font-size:13px; line-height:1.6; color:#8c8577;">
        This link will expire in {{ $expires }} minutes. If you did not request a password reset,
        no further action is required — your password has not changed.
    </p>

    <p style="margin:0; font-size:12px; line-height:1.6; color:#8c8577; word-break:break-all;">
        If the button does not work, copy and paste this address into your browser:<br>
        <a href="{{ $url }}" style="color:#8c8577;">{{ $url }}</a>
    </p>
@endsection
