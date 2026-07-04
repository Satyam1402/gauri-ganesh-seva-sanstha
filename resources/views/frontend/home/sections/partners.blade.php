<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-2 items-center gap-6 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($section->activeItems as $item)
                <a
                    href="{{ $item->link_url ?: '#' }}"
                    class="flex h-16 items-center justify-center rounded-lg border border-border-subtle bg-surface-white px-4 grayscale transition hover:grayscale-0 dark:border-night-border"
                >
                    @if ($item->getFirstMedia('image'))
                        <x-ui.lazy-image :media="$item->getFirstMedia('image')" :alt="$item->title" class="max-h-10 w-auto object-contain" />
                    @else
                        <span class="text-sm font-medium text-text-600">{{ $item->title }}</span>
                    @endif
                </a>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="No partners listed yet" class="mt-10" />
    @endif
</x-ui.section>
