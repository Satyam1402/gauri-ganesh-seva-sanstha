{{--
    Shared layout for every outgoing email.

    `$brand` is injected by the view composer in NotificationServiceProvider
    (App\Support\Notifications\EmailBranding) — organisation name, logo,
    contact details, website, social and legal links all come from Site
    Settings / the organisation profile, never from this file.

    Templates: @extends('emails.layout') and fill @section('body').
    Optional: @section('preheader') for the inbox preview line.
--}}
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="x-apple-disable-message-reformatting">
    <meta name="color-scheme" content="light">
    <title>{{ $brand['name'] }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f2ec; font-family:Arial, Helvetica, sans-serif; color:#2b2620; -webkit-font-smoothing:antialiased;">
    @hasSection('preheader')
        <div style="display:none; max-height:0; overflow:hidden; opacity:0; color:transparent; font-size:1px; line-height:1px;">@yield('preheader')</div>
    @endif

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f2ec; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5ded2;">

                    {{-- Header: logo (falls back to the name) --}}
                    <tr>
                        <td style="background-color:{{ $brand['primaryColor'] }}; padding:24px 32px;">
                            <a href="{{ $brand['website'] }}" style="text-decoration:none;">
                                @if ($brand['logo'])
                                    <img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" height="40" style="display:block; height:40px; width:auto; max-width:220px; border:0;">
                                @else
                                    <span style="display:block; font-size:20px; font-weight:bold; color:#ffffff;">{{ $brand['name'] }}</span>
                                @endif
                            </a>
                        </td>
                    </tr>

                    {{-- Body --}}
                    <tr>
                        <td style="padding:32px;">
                            @yield('body')
                        </td>
                    </tr>

                    {{-- Contact block --}}
                    @if ($brand['email'] || $brand['phone'] || $brand['address'])
                        <tr>
                            <td style="padding:20px 32px; background-color:#faf7f1; border-top:1px solid #e5ded2; font-size:13px; line-height:1.7; color:#54615c;">
                                <p style="margin:0 0 4px; font-weight:bold; color:#2b2620;">{{ $brand['name'] }}</p>
                                @if ($brand['address'])
                                    <p style="margin:0;">{{ $brand['address'] }}</p>
                                @endif
                                @if ($brand['phone'])
                                    <p style="margin:0;">Phone: <a href="tel:{{ preg_replace('/[^0-9+]/', '', $brand['phone']) }}" style="color:{{ $brand['primaryColor'] }}; text-decoration:none;">{{ $brand['phone'] }}</a></p>
                                @endif
                                @if ($brand['email'])
                                    <p style="margin:0;">Email: <a href="mailto:{{ $brand['email'] }}" style="color:{{ $brand['primaryColor'] }}; text-decoration:none;">{{ $brand['email'] }}</a></p>
                                @endif
                                <p style="margin:0;">Web: <a href="{{ $brand['website'] }}" style="color:{{ $brand['primaryColor'] }}; text-decoration:none;">{{ preg_replace('#^https?://#', '', $brand['website']) }}</a></p>
                            </td>
                        </tr>
                    @endif

                    {{-- Footer: social + legal links --}}
                    <tr>
                        <td style="padding:16px 32px 20px; border-top:1px solid #e5ded2; font-size:12px; line-height:1.7; color:#8c8577;">
                            @if ($brand['socials'] !== [])
                                <p style="margin:0 0 6px;">
                                    @foreach ($brand['socials'] as $label => $url)
                                        <a href="{{ $url }}" style="color:#8c8577; text-decoration:underline;">{{ $label }}</a>@if (! $loop->last) &nbsp;&middot;&nbsp; @endif
                                    @endforeach
                                </p>
                            @endif
                            @if ($brand['legal'] !== [])
                                <p style="margin:0 0 6px;">
                                    @foreach ($brand['legal'] as $label => $url)
                                        <a href="{{ $url }}" style="color:#8c8577; text-decoration:underline;">{{ $label }}</a>@if (! $loop->last) &nbsp;&middot;&nbsp; @endif
                                    @endforeach
                                </p>
                            @endif
                            <p style="margin:0;">
                                &copy; {{ $brand['year'] }} {{ $brand['name'] }}. This is an automated message — please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
