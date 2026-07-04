{{-- Visual placeholder only — subscription storage ships with a future Newsletter module. --}}
<x-ui.section background="muted">
    <div class="mx-auto max-w-xl text-center">
        @if ($section->heading)
            <h2 class="font-display text-3xl font-semibold text-text-900">{{ $section->heading }}</h2>
        @endif
        @if ($section->subheading)
            <p class="mt-3 text-text-600">{{ $section->subheading }}</p>
        @endif

        <div class="mt-6 flex flex-col gap-3 sm:flex-row">
            <input
                type="email"
                placeholder="you@example.com"
                disabled
                class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-400 dark:border-night-border dark:bg-night-surface"
            >
            <x-ui.button type="button" disabled class="opacity-60">Subscribe</x-ui.button>
        </div>
    </div>
</x-ui.section>
