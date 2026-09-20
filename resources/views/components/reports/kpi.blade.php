{{-- KPI tile for dashboards and report headers. --}}
@props(['label', 'value', 'hint' => null, 'href' => null, 'money' => false, 'tone' => 'neutral'])

@php
    $display = is_numeric($value) ? ($money ? format_inr((float) $value) : number_format((float) $value)) : $value;
    $tones = [
        'neutral' => 'text-text-900 dark:text-night-text',
        'success' => 'text-success-600',
        'warning' => 'text-warning-600',
        'error' => 'text-error-600',
        'accent' => 'text-accent-500',
    ];
@endphp

<div {{ $attributes->class(['rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface']) }}>
    <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $label }}</p>
    <p class="mt-1 truncate font-display text-2xl font-semibold {{ $tones[$tone] ?? $tones['neutral'] }}" title="{{ $display }}">{{ $display }}</p>
    @if ($hint || $href)
        <p class="mt-1 flex items-center justify-between gap-2 text-xs text-text-400 dark:text-night-text-muted">
            <span>{{ $hint }}</span>
            @if ($href)
                <a href="{{ $href }}" class="shrink-0 font-medium text-primary-700 hover:underline dark:text-night-text">View →</a>
            @endif
        </p>
    @endif
</div>
