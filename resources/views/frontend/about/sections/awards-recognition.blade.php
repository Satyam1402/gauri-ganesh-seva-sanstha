<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($section->activeItems as $item)
                <x-ui.card class="text-center">
                    <div class="mx-auto h-16 w-16 overflow-hidden rounded-full bg-surface-muted">
                        <x-ui.lazy-image :media="$item->getFirstMedia('image')" :alt="$item->title" />
                    </div>
                    <h3 class="mt-4 font-display text-lg font-semibold text-text-900">{{ $item->title }}</h3>
                    @if ($item->subtitle)
                        <x-ui.badge variant="accent" class="mt-2">{{ $item->subtitle }}</x-ui.badge>
                    @endif
                    <p class="mt-2 text-sm text-text-600">{{ $item->description }}</p>
                </x-ui.card>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Awards coming soon" class="mt-10" />
    @endif
</x-ui.section>
