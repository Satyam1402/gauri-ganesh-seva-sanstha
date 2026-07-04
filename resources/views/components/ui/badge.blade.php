@props(['variant' => 'neutral'])

@php
    $variants = [
        'neutral' => 'bg-primary-100 text-primary-700',
        'success' => 'bg-green-100 text-success-600',
        'warning' => 'bg-amber-100 text-warning-600',
        'error' => 'bg-red-100 text-error-600',
        'accent' => 'bg-accent-100 text-accent-500',
    ];
@endphp

<span {{ $attributes->class([
    'inline-flex items-center gap-1 rounded-sm px-2 py-0.5 text-xs font-semibold',
    $variants[$variant] ?? $variants['neutral'],
]) }}>
    {{ $slot }}
</span>
