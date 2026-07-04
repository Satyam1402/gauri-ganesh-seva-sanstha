@props(['heading', 'subheading' => null, 'align' => 'left'])

<div {{ $attributes->class(['max-w-2xl', 'mx-auto text-center' => $align === 'center']) }}>
    <h2 class="text-3xl font-display font-semibold text-text-900 dark:text-night-text">{{ $heading }}</h2>

    @if ($subheading)
        <p class="mt-3 text-lg text-text-600 dark:text-night-text-muted">{{ $subheading }}</p>
    @endif
</div>
