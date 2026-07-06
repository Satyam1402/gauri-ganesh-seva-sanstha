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

        @case('map-pin')
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21s7-6.5 7-11.5A7 7 0 0 0 5 9.5C5 14.5 12 21 12 21Z" />
            <circle cx="12" cy="9.5" r="2.25" />
            @break

        @case('clock')
            <circle cx="12" cy="12" r="8.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 2" />
            @break

        @case('share')
            <circle cx="18" cy="5.5" r="2.25" />
            <circle cx="6" cy="12" r="2.25" />
            <circle cx="18" cy="18.5" r="2.25" />
            <path stroke-linecap="round" d="m8 10.8 8-4.3M8 13.2l8 4.3" />
            @break

        @case('facebook')
            <path stroke-linecap="round" stroke-linejoin="round" d="M14 8.5h2V5.3h-2.3C11.5 5.3 10 6.8 10 9v2H8v3h2v6.7h3V14h2.2l.5-3H13V9.3c0-.4.3-.8.7-.8Z" />
            @break

        @case('whatsapp')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6.5 17.5 5 20l2.6-1.4A7.5 7.5 0 1 0 5.5 14l1 3.5Z" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 9.8c.2-.5.6-.5.9-.5s.5 0 .7.5.7 1.6.7 1.7 0 .3-.2.5c-.2.3-.4.4-.6.6-.2.2-.4.4-.2.7.2.4 1 1.4 2.1 2.1.4.3.7.3 1 0 .2-.2.6-.7.8-1 .2-.2.4-.2.6-.1s1.5.7 1.8.8.5.2.5.3c0 .2 0 .8-.3 1.2s-1 .9-1.7.9c-.6 0-2.6-.3-4.4-2.1-1.8-1.8-2.4-3.6-2.5-4.2-.1-.6.4-1.4.8-1.5Z" />
            @break

        @case('link')
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 15 9m-5-2.5 1-1a3.5 3.5 0 0 1 5 5l-1 1m-3 6-1 1a3.5 3.5 0 0 1-5-5l1-1" />
            @break

        @case('x-twitter')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4.5 19 19.5M19 4.5 5 19.5" />
            @break

        @case('instagram')
            <rect x="4" y="4" width="16" height="16" rx="4" />
            <circle cx="12" cy="12" r="3.5" />
            <circle cx="16.75" cy="7.25" r="0.75" fill="currentColor" />
            @break

        @case('youtube')
            <rect x="3" y="6" width="18" height="12" rx="3" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 9.5v5l4.5-2.5-4.5-2.5Z" />
            @break

        @case('linkedin')
            <rect x="4" y="4" width="16" height="16" rx="2" />
            <path stroke-linecap="round" d="M8 11v5M8 8.2v.05M12 16v-3a2 2 0 0 1 4 0v3" />
            @break

        @case('phone')
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 4.5h3l1.5 4-2 1.5a12 12 0 0 0 6.5 6.5l1.5-2 4 1.5v3a1.5 1.5 0 0 1-1.6 1.5C10.2 19.9 4.1 13.8 3.5 6.1A1.5 1.5 0 0 1 5 4.5Z" />
            @break

        @case('envelope')
            <rect x="3.5" y="5.5" width="17" height="13" rx="1.5" />
            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 7 7.5 6 7.5-6" />
            @break

        @case('check')
            <path stroke-linecap="round" stroke-linejoin="round" d="m5 12.5 4.5 4.5L19 7" />
            @break

        @case('x-mark')
            <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
            @break

        @default
            <circle cx="12" cy="12" r="8.5" />
    @endswitch
</svg>
