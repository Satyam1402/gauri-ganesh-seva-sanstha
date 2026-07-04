@props([
    'label' => null,
    'name',
    'type' => 'text',
    'error' => null,
    'helper' => null,
])

@php $inputId = $attributes->get('id') ?? $name; @endphp

<div>
    @if ($label)
        <label for="{{ $inputId }}" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">
            {{ $label }}
        </label>
    @endif

    <input
        id="{{ $inputId }}"
        name="{{ $name }}"
        type="{{ $type }}"
        {{ $attributes->except(['label', 'name', 'type', 'error', 'helper'])->class([
            'block w-full rounded-md border bg-surface-white px-4 py-2.5 text-base text-text-900 placeholder:text-text-400',
            'dark:bg-night-surface dark:text-night-text dark:placeholder:text-night-text-muted',
            'focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35',
            'border-error-600' => $error,
            'border-border-subtle dark:border-night-border' => ! $error,
        ]) }}
        @if ($error) aria-invalid="true" aria-describedby="{{ $inputId }}-error" @endif
    />

    @if ($error)
        <p id="{{ $inputId }}-error" class="mt-1.5 text-xs text-error-600">{{ $error }}</p>
    @elseif ($helper)
        <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ $helper }}</p>
    @endif
</div>
