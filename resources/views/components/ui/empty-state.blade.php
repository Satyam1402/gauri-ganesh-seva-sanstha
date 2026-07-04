@props(['heading', 'message' => null])

<div {{ $attributes->class([
    'flex flex-col items-center justify-center rounded-lg border border-dashed border-border-subtle p-10 text-center dark:border-night-border',
]) }}>
    <div class="flex h-12 w-12 items-center justify-center rounded-full bg-surface-muted text-text-400 dark:bg-night-surface-alt dark:text-night-text-muted">
        @isset($icon)
            {{ $icon }}
        @else
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12.75V12A9.75 9.75 0 0 1 12 2.25v0A9.75 9.75 0 0 1 21.75 12v.75m-19.5 0v6a2.25 2.25 0 0 0 2.25 2.25h1.5a2.25 2.25 0 0 0 2.25-2.25v-1.5a2.25 2.25 0 0 0-2.25-2.25h-3.75Zm19.5 0v6a2.25 2.25 0 0 1-2.25 2.25h-1.5a2.25 2.25 0 0 1-2.25-2.25v-1.5a2.25 2.25 0 0 1 2.25-2.25h3.75Z" />
            </svg>
        @endisset
    </div>

    <p class="mt-4 font-semibold text-text-900 dark:text-night-text">{{ $heading }}</p>

    @if ($message)
        <p class="mt-1 max-w-sm text-sm text-text-400 dark:text-night-text-muted">{{ $message }}</p>
    @endif

    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
