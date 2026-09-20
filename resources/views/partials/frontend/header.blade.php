{{--
    Site header. Brand, navigation and buttons all come from Site Settings
    (setting()/site_menu() read one cached map — no queries here).
--}}
@php
    $siteName = setting('general.site_name', config('app.name'));
    $logo = setting_media('branding.logo', 'webp');
    $menu = site_menu('header');
    $ctaLabel = setting('navigation.header_cta_label');
    $ctaUrl = setting('navigation.header_cta_url');
    $secondaryLabel = setting('navigation.header_secondary_label');
    $secondaryUrl = setting('navigation.header_secondary_url');
@endphp

<header x-data="{ mobileOpen: false }" class="sticky top-0 z-40 border-b border-border-subtle bg-surface-white/95 backdrop-blur">
    <div class="mx-auto flex max-w-[1360px] items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-3 font-display text-xl font-semibold text-primary-700" aria-label="{{ $siteName }} — home">
            @if ($logo)
                <img src="{{ $logo }}" alt="{{ $siteName }}" class="h-10 w-auto" width="160" height="40" decoding="async">
            @else
                {{ $siteName }}
            @endif
        </a>

        <nav class="hidden items-center gap-8 text-sm font-medium text-text-600 lg:flex" aria-label="Main navigation">
            @foreach ($menu as $item)
                @if ($item->children->isNotEmpty())
                    <div x-data="{ open: false }" @keydown.escape="open = false" @click.outside="open = false" class="relative">
                        <button
                            type="button"
                            @click="open = ! open"
                            :aria-expanded="open ? 'true' : 'false'"
                            aria-haspopup="true"
                            class="inline-flex items-center gap-1 rounded hover:text-primary-700 focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35 {{ $item->isCurrent() ? 'text-primary-700' : '' }}"
                        >
                            {{ $item->label }}
                            <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-3.5 w-3.5 transition-transform" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>
                        <div x-show="open" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 top-full z-50 mt-2 min-w-[12rem] rounded-lg border border-border-subtle bg-surface-white py-2 shadow-lg">
                            @foreach ($item->children as $child)
                                <a href="{{ $child->href() }}" @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="block px-4 py-2 text-sm text-text-600 hover:bg-surface-muted hover:text-primary-700" @if ($child->isCurrent()) aria-current="page" @endif>{{ $child->label }}</a>
                            @endforeach
                        </div>
                    </div>
                @else
                    <a href="{{ $item->href() }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="hover:text-primary-700 {{ $item->isCurrent() ? 'text-primary-700' : '' }}" @if ($item->isCurrent()) aria-current="page" @endif>{{ $item->label }}</a>
                @endif
            @endforeach
        </nav>

        <div class="hidden items-center gap-3 lg:flex">
            @if ($secondaryLabel && $secondaryUrl)
                <x-ui.button href="{{ $secondaryUrl }}" variant="ghost" size="sm">{{ $secondaryLabel }}</x-ui.button>
            @endif
            @if ($ctaLabel && $ctaUrl)
                <x-ui.button href="{{ $ctaUrl }}" variant="accent" size="sm">{{ $ctaLabel }}</x-ui.button>
            @endif
        </div>

        <button
            type="button"
            @click="mobileOpen = !mobileOpen"
            :aria-expanded="mobileOpen ? 'true' : 'false'"
            aria-controls="mobile-navigation"
            class="inline-flex items-center justify-center rounded-md p-2 text-text-900 lg:hidden"
            aria-label="Toggle navigation menu"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5M3.75 17.25h16.5" />
            </svg>
        </button>
    </div>

    <div
        id="mobile-navigation"
        x-show="mobileOpen"
        x-cloak
        x-transition
        class="border-t border-border-subtle bg-surface-white lg:hidden"
    >
        <nav class="flex flex-col gap-1 px-4 py-4 text-base font-medium text-text-600" aria-label="Mobile navigation">
            @foreach ($menu as $item)
                <a href="{{ $item->href() }}" @if ($item->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700" @if ($item->isCurrent()) aria-current="page" @endif>{{ $item->label }}</a>
                @foreach ($item->children as $child)
                    <a href="{{ $child->href() }}" @if ($child->open_in_new_tab) target="_blank" rel="noopener noreferrer" @endif class="rounded-md px-3 py-2 pl-8 text-sm hover:bg-surface-muted hover:text-primary-700">{{ $child->label }}</a>
                @endforeach
            @endforeach
            @if ($secondaryLabel && $secondaryUrl)
                <a href="{{ $secondaryUrl }}" class="rounded-md px-3 py-2 hover:bg-surface-muted hover:text-primary-700">{{ $secondaryLabel }}</a>
            @endif
            @if ($ctaLabel && $ctaUrl)
                <x-ui.button href="{{ $ctaUrl }}" variant="accent" size="md" class="mt-3 justify-center">{{ $ctaLabel }}</x-ui.button>
            @endif
        </nav>
    </div>
</header>
