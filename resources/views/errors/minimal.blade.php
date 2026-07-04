<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Error') &middot; {{ config('app.name') }}</title>
    @vite(['resources/css/app.css'])
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-bg-base px-4 text-center">
    <p class="font-display text-7xl font-semibold text-primary-700">@yield('code')</p>

    <h1 class="mt-4 text-2xl font-semibold text-text-900">@yield('heading')</h1>
    <p class="mt-3 max-w-md text-text-600">@yield('message')</p>

    <div class="mt-8 flex gap-3">
        <a href="{{ url('/') }}" class="inline-flex h-11 items-center justify-center rounded-md bg-primary-700 px-6 font-semibold text-white shadow-sm hover:bg-primary-800">
            Back to Home
        </a>
        <a href="#" class="inline-flex h-11 items-center justify-center rounded-md border border-primary-700 px-6 font-semibold text-primary-700 hover:bg-primary-700 hover:text-white">
            Contact Us
        </a>
    </div>
</body>
</html>
