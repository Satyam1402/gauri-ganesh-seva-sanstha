<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mx-auto mt-10 max-w-2xl space-y-8 border-l-2 border-border-subtle pl-6 dark:border-night-border">
            @foreach ($section->activeItems as $item)
                <div class="relative">
                    <span class="absolute -left-[1.95rem] top-1 h-3 w-3 rounded-full bg-primary-700"></span>
                    <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">{{ $item->title }}</p>
                    <h3 class="mt-1 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $item->subtitle }}</h3>
                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $item->description }}</p>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Our journey is being written" class="mx-auto mt-10 max-w-md" />
    @endif
</x-ui.section>
