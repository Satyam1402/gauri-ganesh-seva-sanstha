<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('meta_description', 'Gauri Ganesh Seva Sanstha — food, education, medical, and social welfare seva.')">
    @hasSection('meta_keywords')
        <meta name="keywords" content="@yield('meta_keywords')">
    @endif
    <link rel="canonical" href="@yield('canonical_url', url()->current())">

    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'Together, we restore dignity.')">
    <meta property="og:type" content="website">
    <meta property="og:url" content="{{ url()->current() }}">
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @endif

    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('og_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('og_description', 'Together, we restore dignity.')">
    @hasSection('og_image')
        <meta name="twitter:image" content="@yield('og_image')">
    @endif

    @stack('structured_data')

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
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
