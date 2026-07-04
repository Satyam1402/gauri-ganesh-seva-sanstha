<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @foreach ($section->activeItems as $item)
                <div class="aspect-square overflow-hidden rounded-lg bg-surface-white">
                    <x-ui.lazy-image :media="$item->getFirstMedia('image')" :alt="$item->title" />
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Gallery coming soon" class="mt-10" />
    @endif

    @foreach ($section->buttons as $button)
        <div class="mt-10 text-center">
            <x-ui.button href="{{ $button->url }}" :variant="$button->variant">{{ $button->label }}</x-ui.button>
        </div>
    @endforeach
</x-ui.section>
