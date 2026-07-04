@props(['size' => 'md', 'label' => 'Loading…'])

@php
    $sizes = [
        'sm' => 'h-4 w-4',
        'md' => 'h-6 w-6',
        'lg' => 'h-10 w-10',
    ];
@endphp

<span {{ $attributes->class(['inline-flex items-center gap-2 text-text-600 dark:text-night-text-muted']) }} role="status">
    <svg class="{{ $sizes[$size] ?? $sizes['md'] }} animate-spin text-primary-700 dark:text-night-text" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4Z"></path>
    </svg>
    <span class="sr-only">{{ $label }}</span>
</span>
