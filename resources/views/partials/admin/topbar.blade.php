<header class="flex h-16 items-center justify-between border-b border-border-subtle bg-surface-white px-6 dark:border-night-border dark:bg-night-surface">
    <div class="flex items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = !sidebarOpen"
            class="rounded-md p-2 text-text-600 hover:bg-surface-muted lg:hidden dark:text-night-text-muted dark:hover:bg-night-surface-alt"
            aria-label="Toggle sidebar"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>

        <h1 class="text-lg font-semibold text-text-900 dark:text-night-text">@yield('title', 'Dashboard')</h1>
    </div>

    <div class="flex items-center gap-2">
        {{-- Dark mode toggle --}}
        <button
            type="button"
            x-data
            @click="$store.theme.toggle()"
            class="rounded-md p-2 text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt"
            aria-label="Toggle dark mode"
        >
            <svg x-show="! $store.theme.dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
            <svg x-cloak x-show="$store.theme.dark" xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
            </svg>
        </button>

        {{-- Notifications — unread count and recent items come from the NotificationBell class component --}}
        <x-admin.notification-bell />

        {{-- Profile dropdown --}}
        <div class="relative" x-data="{ open: false }">
            <button
                type="button"
                @click="open = !open"
                @click.outside="open = false"
                class="flex items-center gap-2 rounded-md px-2 py-1.5 text-sm text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                    {{ Str::of(auth()->user()->name)->substr(0, 1)->upper() }}
                </span>
                <span class="hidden sm:block">{{ auth()->user()->name }}</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                </svg>
            </button>

            <div
                x-show="open"
                x-cloak
                x-transition
                class="absolute right-0 z-40 mt-2 w-56 rounded-lg border border-border-subtle bg-surface-white p-2 text-sm shadow-lg dark:border-night-border dark:bg-night-surface"
            >
                <div class="border-b border-border-subtle px-3 py-2 dark:border-night-border">
                    <p class="font-medium text-text-900 dark:text-night-text">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-text-400 dark:text-night-text-muted">{{ auth()->user()->email }}</p>
                </div>

                <a href="{{ route('admin.profile.edit') }}" class="block rounded-md px-3 py-2 text-text-600 hover:bg-surface-muted dark:text-night-text-muted dark:hover:bg-night-surface-alt">
                    My Profile
                </a>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="block w-full rounded-md px-3 py-2 text-left text-error-600 hover:bg-red-50 dark:hover:bg-night-surface-alt">
                        Log Out
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
