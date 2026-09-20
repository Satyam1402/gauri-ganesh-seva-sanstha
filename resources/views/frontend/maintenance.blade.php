{{--
    Maintenance page served by EnsureSiteIsNotInMaintenance (HTTP 503).
    Deliberately standalone — no header/footer/menus — so it never depends
    on anything that might itself be mid-change. Contains no debug output.
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $heading }} &middot; {{ setting('general.site_name', config('app.name')) }}</title>
    @if ($favicon = setting_media('branding.favicon'))
        <link rel="icon" href="{{ $favicon }}">
    @endif
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-bg-base px-4 text-center">
    @if ($logo = setting_media('branding.logo', 'webp'))
        <img src="{{ $logo }}" alt="{{ setting('general.site_name', config('app.name')) }}" class="mb-8 h-14 w-auto" width="200" height="56">
    @else
        <p class="mb-8 font-display text-2xl font-semibold text-primary-700">{{ setting('general.site_name', config('app.name')) }}</p>
    @endif

    <h1 class="text-3xl font-semibold text-text-900">{{ $heading }}</h1>

    @if ($message)
        <p class="mt-3 max-w-md text-text-600">{{ $message }}</p>
    @endif

    @if ($expectedBackAt)
        <p class="mt-4 text-sm text-text-400">
            Expected back: <time datetime="{{ \Illuminate\Support\Carbon::parse($expectedBackAt)->toIso8601String() }}">{{ \Illuminate\Support\Carbon::parse($expectedBackAt)->format(setting('general.date_format', 'd M Y').', g:i A') }}</time>
        </p>
    @endif

    @if ($email = setting('contact.email_primary'))
        <p class="mt-8 text-sm text-text-600">
            Need us urgently? <a href="mailto:{{ $email }}" class="font-medium text-primary-700 underline underline-offset-2">{{ $email }}</a>
        </p>
    @endif
</body>
</html>
