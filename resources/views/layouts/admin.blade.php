<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@yield('title', 'Admin') &middot; {{ config('app.name') }}</title>

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
<body class="flex min-h-screen bg-surface-muted text-text-900 dark:bg-night-bg dark:text-night-text" x-data="{ sidebarOpen: false }">

    @include('partials.admin.sidebar')

    <div class="flex min-h-screen flex-1 flex-col lg:ml-60">
        @include('partials.admin.topbar')

        <main class="flex-1 p-6">
            @hasSection('breadcrumbs')
                <div class="mb-4">
                    @yield('breadcrumbs')
                </div>
            @endif

            @include('partials.admin.flash-messages')

            @yield('content')
        </main>

        @include('partials.admin.footer')
    </div>

    @stack('scripts')
</body>
</html>
