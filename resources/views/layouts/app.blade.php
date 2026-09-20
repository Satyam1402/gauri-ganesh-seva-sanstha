<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    @php
        // Every public view receives $seo (App\Support\Seo\SeoData) from its
        // controller via SeoService; anything rendered without one gets the
        // site-wide defaults. Branding values come from the cached settings map.
        $seo ??= app(App\Services\SeoService::class)->defaults();
        $favicon = setting_media('branding.favicon');
        $primaryColor = setting('branding.primary_color');
        $accentColor = setting('branding.secondary_color');
    @endphp

    @include('partials.frontend.seo-head', ['seo' => $seo])

    @if ($favicon)
        <link rel="icon" href="{{ $favicon }}">
    @endif

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @if ($primaryColor || $accentColor)
        {{-- Brand colour overrides: values are validated as #RRGGBB before they are stored. --}}
        <style>:root{ {{ $primaryColor ? '--color-primary-700:'.$primaryColor.';' : '' }}{{ $accentColor ? '--color-accent-500:'.$accentColor.';' : '' }} }</style>
    @endif
    @stack('styles')
    @include('partials.frontend.analytics')
</head>
<body class="flex min-h-screen flex-col bg-bg-base text-text-900">

    @include('partials.frontend.header')

    @include('partials.frontend.flash-messages')

    <main class="flex-1">
        @yield('content')
    </main>

    @include('partials.frontend.footer')

    <x-ui.scroll-to-top />

    @stack('scripts')
</body>
</html>
