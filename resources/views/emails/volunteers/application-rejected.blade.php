@extends('emails.volunteers.layout')

@section('body')
    <h1 style="margin:0 0 16px; font-size:22px; color:#2b2620;">Thank You for Your Interest, {{ $application->first_name }}</h1>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        Thank you for taking the time to apply as a volunteer with
        {{ config('app.name') }}. After careful review, we are unable to move
        forward with your application at this time.
    </p>

    <p style="margin:0 0 16px; font-size:14px; line-height:1.6;">
        This is often simply a matter of current capacity or a mismatch with the
        roles we need filled right now — it is not a reflection of your worth or
        willingness to serve. We warmly encourage you to apply again in the future.
    </p>

    <p style="margin:0 0 24px; font-size:14px; line-height:1.6;">
        In the meantime, you can continue supporting our mission by attending our
        events, spreading the word, or contributing to our campaigns.
    </p>

    <table role="presentation" cellpadding="0" cellspacing="0">
        <tr>
            <td style="border-radius:6px; background-color:#8a3324;">
                <a href="{{ route('home') }}" style="display:inline-block; padding:12px 24px; font-size:14px; font-weight:bold; color:#ffffff; text-decoration:none;">
                    Explore Other Ways to Help
                </a>
            </td>
        </tr>
    </table>

    <p style="margin:24px 0 0; font-size:14px; line-height:1.6;">
        With gratitude,<br>
        <strong>The {{ config('app.name') }} Team</strong>
    </p>
@endsection
