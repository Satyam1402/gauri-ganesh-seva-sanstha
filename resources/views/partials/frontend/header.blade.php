<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-border-subtle bg-surface-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-[1360px] items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="font-display text-xl font-semibold text-primary-700">
            {{ config('app.name') }}
        </a>

        {{-- Placeholder links below become route()/named-route links as each module ships its routes. --}}
        <nav class="hidden items-center gap-8 text-sm font-medium text-text-600 lg:flex">
            <a href="{{ route('about') }}" class="hover:text-primary-700">About</a>
            <a href="#" class="hover:text-primary-700">Programs</a>
            <a href="#" class="hover:text-primary-700">Campaigns</a>
            <a href="#" class="hover:text-primary-700">Gallery</a>
            <a href="{{ route('blog.index') }}" class="hover:text-primary-700">Blog</a>
            <a href="#" class="hover:text-primary-700">Contact</a>
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            <x-ui.button href="#" variant="ghost" size="sm">Get Involved</x-ui.button>
            <x-ui.button href="#" variant="accent" size="sm">Donate Now</x-ui.button>
        </div>

        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            class="inline-flex items-center justify-center rounded-md p-2 text-text-900 lg:hidden"
            aria-label="Toggle navigation menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>
    </div>

    <div
        x-show="mobileOpen"
        x-cloak
        x-transition
        class="border-t border-border-subtle bg-surface-white lg:hidden"
    >
        <nav class="flex flex-col gap-1 px-4 py-4 text-base font-medium text-text-600">
            <a href="{{ route('about') }}" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">About</a>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">Programs</a>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">Campaigns</a>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">Gallery</a>
            <a href="{{ route('blog.index') }}" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">Blog</a>
            <a href="#" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">Contact</a>
            <x-ui.button href="#" variant="accent" size="md" class="mt-3 justify-center">Donate Now</x-ui.button>
        </nav>
    </div>
</header>
