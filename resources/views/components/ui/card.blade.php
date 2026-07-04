@props(['hoverable' => false])

<div {{ $attributes->class([
    'rounded-lg border border-border-subtle bg-surface-white p-6 shadow-sm dark:border-night-border dark:bg-night-surface',
    'transition-shadow duration-200 hover:shadow-md' => $hoverable,
]) }}>
    {{ $slot }}
</div>
