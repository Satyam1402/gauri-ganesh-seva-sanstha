@props(['background' => 'base', 'id' => null, 'spacing' => 'lg'])

@php
    $backgrounds = [
        'base' => 'bg-bg-base',
        'white' => 'bg-surface-white',
        'muted' => 'bg-surface-muted',
        'dark' => 'bg-primary-800 text-text-inverse',
    ];

    $spacings = [
        'sm' => 'py-8 lg:py-12',
        'lg' => 'py-16 lg:py-24',
    ];
@endphp

<section
    @if ($id) id="{{ $id }}" @endif
    {{ $attributes->class([
        $backgrounds[$background] ?? $backgrounds['base'],
        $spacings[$spacing] ?? $spacings['lg'],
    ]) }}
>
    <x-ui.container>
        {{ $slot }}
    </x-ui.container>
</section>
