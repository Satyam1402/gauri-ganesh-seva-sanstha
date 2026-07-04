@props(['headings' => []])

<div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
    <table {{ $attributes->class(['w-full text-left text-sm']) }}>
        @if (count($headings))
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    @foreach ($headings as $heading)
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{{ $heading }}</th>
                    @endforeach
                </tr>
            </thead>
        @endif

        <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
            {{ $slot }}
        </tbody>
    </table>
</div>
