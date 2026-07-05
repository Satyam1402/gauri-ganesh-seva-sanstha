<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#f5f2ec; font-family:Arial, Helvetica, sans-serif; color:#2b2620;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f5f2ec; padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden; border:1px solid #e5ded2;">
                    <tr>
                        <td style="background-color:#8a3324; padding:24px 32px;">
                            <p style="margin:0; font-size:20px; font-weight:bold; color:#ffffff;">{{ config('app.name') }}</p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            @yield('body')
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 32px; background-color:#faf7f1; border-top:1px solid #e5ded2;">
                            <p style="margin:0; font-size:12px; color:#8c8577;">
                                {{ config('app.name') }} &middot; This is an automated message — please do not reply directly to this email.
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
