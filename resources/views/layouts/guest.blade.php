<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', config('app.name'))</title>

    <script>
        (function () {
            var theme = localStorage.theme;
            var prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            if (theme === 'dark' || (! theme && prefersDark)) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="flex min-h-screen flex-col items-center justify-center bg-surface-muted px-4 py-12 dark:bg-night-bg">

    <div class="mb-8">
        <a href="{{ route('home') }}" class="font-display text-2xl font-semibold text-primary-700 dark:text-night-text">
            {{ config('app.name') }}
        </a>
    </div>

    <div class="w-full max-w-md rounded-xl border border-border-subtle bg-surface-white p-8 shadow-sm dark:border-night-border dark:bg-night-surface">
        @yield('content')
    </div>

    @stack('scripts')
</body>
</html>
