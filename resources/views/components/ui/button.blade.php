@props([
    'variant' => 'primary',
    'size' => 'md',
    'href' => null,
    'type' => 'button',
])

@php
    $variants = [
        'primary' => 'bg-primary-700 text-white hover:bg-primary-800 shadow-sm hover:shadow-md',
        'accent' => 'bg-accent-500 text-white hover:brightness-95 shadow-sm hover:shadow-md',
        'secondary' => 'bg-transparent text-primary-700 border border-primary-700 hover:bg-primary-700 hover:text-white',
        'ghost' => 'bg-transparent text-primary-700 hover:underline',
        'danger' => 'bg-error-600 text-white hover:brightness-95',
    ];

    $sizes = [
        'sm' => 'h-9 px-4 text-sm',
        'md' => 'h-11 px-5 text-base',
        'lg' => 'h-13 px-7 text-lg',
    ];

    $classes = 'inline-flex items-center justify-center gap-2 rounded-md font-semibold transition duration-150 ease-out disabled:opacity-40 disabled:cursor-not-allowed '
        . ($variants[$variant] ?? $variants['primary']) . ' '
        . ($sizes[$size] ?? $sizes['md']);
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->class([$classes]) }}>
        {{ $slot }}
    </button>
@endif
