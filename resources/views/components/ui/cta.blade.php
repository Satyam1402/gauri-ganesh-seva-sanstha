@props(['heading', 'subheading' => null, 'variant' => 'dark'])

@php
    $variants = [
        'dark' => 'bg-primary-800 text-text-inverse',
        'muted' => 'bg-surface-muted text-text-900',
        'white' => 'bg-surface-white text-text-900 border border-border-subtle',
    ];
@endphp

<div {{ $attributes->class([
    'rounded-2xl px-6 py-12 text-center sm:px-12 sm:py-16',
    $variants[$variant] ?? $variants['dark'],
]) }}>
    <h2 class="font-display text-3xl font-semibold">{{ $heading }}</h2>

    @if ($subheading)
        <p class="mx-auto mt-3 max-w-2xl text-lg opacity-90">{{ $subheading }}</p>
    @endif

    @if ($slot->isNotEmpty())
        <div class="mt-8 flex flex-wrap items-center justify-center gap-4">
            {{ $slot }}
        </div>
    @endif
</div>
