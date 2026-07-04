@props(['name', 'maxWidth' => 'md'])

@php
    $maxWidths = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-2xl',
    ];
@endphp

<div
    x-data="{ open: false }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') open = true"
    x-on:close-modal.window="if (! $event.detail || $event.detail === '{{ $name }}') open = false"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div x-show="open" x-transition.opacity @click="open = false" class="fixed inset-0 bg-primary-900/60"></div>

    <div
        x-show="open"
        x-transition
        x-trap.noscroll="open"
        class="relative w-full {{ $maxWidths[$maxWidth] ?? $maxWidths['md'] }} rounded-xl bg-surface-white p-6 shadow-lg dark:bg-night-surface"
    >
        <button
            type="button"
            @click="open = false"
            class="absolute right-4 top-4 text-text-400 hover:text-text-900 dark:text-night-text-muted dark:hover:text-night-text"
            aria-label="Close"
        >
            &times;
        </button>

        {{ $slot }}
    </div>
</div>
