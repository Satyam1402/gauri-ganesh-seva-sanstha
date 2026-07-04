{{-- Small custom line-icon set (UI-UX-BLUEPRINT Section 10) — not a 1:1 icon-library import, just enough shapes for the current CMS icon fields. --}}
@props(['name' => null])

<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" {{ $attributes->class(['h-6 w-6']) }}>
    @switch($name)
        @case('heart')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 20.25c-4.638-2.936-8.25-6.451-8.25-10.238A4.512 4.512 0 0 1 8.25 5.25c1.847 0 3.417 1.14 4.5 2.828 1.083-1.688 2.653-2.828 4.5-2.828a4.512 4.512 0 0 1 4.5 4.762c0 3.787-3.612 7.302-8.25 10.238Z" />
            @break

        @case('academic-cap')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3 2 8l10 5 10-5-10-5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 10.5v4.5c0 1.5 2.5 3 6 3s6-1.5 6-3v-4.5" />
            @break

        @case('cake')
            <rect x="4" y="12" width="16" height="7" rx="1.5" />
            <path stroke-linecap="round" d="M8 12V9m4 3V9m4 3V9" />
            @break

        @case('sparkles')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v4m0 10v4M3 12h4m10 0h4M6 6l2.5 2.5M15.5 15.5 18 18M18 6l-2.5 2.5M8.5 15.5 6 18" />
            @break

        @case('users')
            <circle cx="9" cy="8" r="3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.5 19.5c0-3 2.5-5 5.5-5s5.5 2 5.5 5" />
            <circle cx="17" cy="9" r="2.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.5c.2-2.3 1.7-4 3.8-4.4" />
            @break

        @case('shield-check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3.5 5 6v5c0 5 3 8.5 7 9.5 4-1 7-4.5 7-9.5V6l-7-2.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 12 2 2 3.5-4" />
            @break

        @case('document-check')
            <path stroke-linecap="round" stroke-linejoin="round" d="M7 3.5h7l3 3v13a1 1 0 0 1-1 1H7a1 1 0 0 1-1-1v-15a1 1 0 0 1 1-1Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m9.5 13 2 2 3.5-4" />
            @break

        @case('chart-bar')
            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20V10m6.5 10V4M17 20v-7" />
            @break

        @case('calendar')
            <rect x="3.5" y="5" width="17" height="15" rx="1.5" />
            <path stroke-linecap="round" d="M3.5 9.5h17M8 3v3.5M16 3v3.5" />
            @break

        @default
            <circle cx="12" cy="12" r="8.5" />
    @endswitch
</svg>
