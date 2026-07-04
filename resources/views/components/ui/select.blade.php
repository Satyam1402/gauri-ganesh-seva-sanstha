@props([
    'label' => null,
    'name',
    'options' => [],
    'selected' => null,
    'error' => null,
    'helper' => null,
])

@php $selectId = $attributes->get('id') ?? $name; @endphp

<div>
    @if ($label)
        <label for="{{ $selectId }}" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">
            {{ $label }}
        </label>
    @endif

    <select
        id="{{ $selectId }}"
        name="{{ $name }}"
        {{ $attributes->except(['label', 'name', 'options', 'selected', 'error', 'helper'])->class([
            'block w-full appearance-none rounded-md border bg-surface-white bg-[url(\'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="%2354615C"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>\')] bg-[right_0.75rem_center] bg-no-repeat px-4 py-2.5 pr-10 text-base text-text-900',
            'dark:bg-night-surface dark:text-night-text',
            'focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35',
            'border-error-600' => $error,
            'border-border-subtle dark:border-night-border' => ! $error,
        ]) }}
    >
        @foreach ($options as $value => $label_)
            <option value="{{ $value }}" @selected((string) $value === (string) $selected)>{{ $label_ }}</option>
        @endforeach
    </select>

    @if ($error)
        <p class="mt-1.5 text-xs text-error-600">{{ $error }}</p>
    @elseif ($helper)
        <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ $helper }}</p>
    @endif
</div>
