<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mx-auto mt-10 max-w-3xl space-y-4">
            @foreach ($section->activeItems as $item)
                <div class="flex gap-4 rounded-lg border border-border-subtle bg-surface-white p-5 dark:border-night-border dark:bg-night-surface">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                        {{ $loop->iteration }}
                    </span>
                    <div>
                        <h3 class="font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</h3>
                        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $item->description }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Objectives coming soon" class="mx-auto mt-10 max-w-md" />
    @endif
</x-ui.section>
