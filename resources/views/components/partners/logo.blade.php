{{--
    Partner logo in a fixed-ratio box (2:1) so the grid never shifts while
    images load. Logos are scaled to fit, never cropped. Without a logo the
    organisation name is shown as text — the name is *always* present for
    screen readers via the alt text or the visible fallback.
--}}
@props(['partner', 'size' => 'md'])

@php
    $logo = $partner->getFirstMedia('logo');
    $padding = $size === 'sm' ? 'p-3' : 'p-5';
@endphp

<div {{ $attributes->class(['flex aspect-[2/1] w-full items-center justify-center overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:border-night-border dark:bg-night-surface', $padding]) }}>
    @if ($logo)
        <img
            src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}"
            alt="{{ $partner->logoAlt() }}"
            width="480"
            height="240"
            loading="lazy"
            decoding="async"
            class="max-h-full max-w-full object-contain"
        >
    @else
        <span class="text-center font-display text-base font-semibold leading-tight text-text-900 dark:text-night-text">{{ $partner->name }}</span>
    @endif
</div>
