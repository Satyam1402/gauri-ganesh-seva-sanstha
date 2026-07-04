@props(['variant' => 'info', 'dismissible' => false])

@php
    $variants = [
        'info' => 'border-primary-700 bg-primary-100 text-primary-800',
        'success' => 'border-success-600 bg-green-50 text-success-600',
        'warning' => 'border-warning-600 bg-amber-50 text-warning-600',
        'error' => 'border-error-600 bg-red-50 text-error-600',
    ];
@endphp

<div
    @if($dismissible) x-data="{ visible: true }" x-show="visible" @endif
    {{ $attributes->class([
        'flex items-start justify-between gap-3 rounded-md border-l-4 px-4 py-3 text-sm',
        $variants[$variant] ?? $variants['info'],
    ]) }}
>
    <div>{{ $slot }}</div>

    @if ($dismissible)
        <button type="button" @click="visible = false" class="shrink-0 text-current/60 hover:text-current" aria-label="Dismiss">
            &times;
        </button>
    @endif
</div>
