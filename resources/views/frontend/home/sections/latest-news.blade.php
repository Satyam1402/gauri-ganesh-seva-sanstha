<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ($section->activeItems as $item)
                <x-ui.card hoverable>
                    <div class="aspect-[16/10] overflow-hidden rounded-lg bg-surface-muted">
                        <x-ui.lazy-image :media="$item->getFirstMedia('image')" :alt="$item->title" />
                    </div>
                    @if ($item->subtitle)
                        <x-ui.badge variant="neutral" class="mt-3">{{ $item->subtitle }}</x-ui.badge>
                    @endif
                    <h3 class="mt-2 font-display text-lg font-semibold text-text-900">{{ $item->title }}</h3>
                    <p class="mt-2 text-sm text-text-600">{{ $item->description }}</p>
                    @if ($item->link_url)
                        <x-ui.button href="{{ $item->link_url }}" variant="ghost" size="sm" class="mt-3">Read More</x-ui.button>
                    @endif
                </x-ui.card>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="No posts published yet" class="mt-10" />
    @endif

    @foreach ($section->buttons as $button)
        <div class="mt-10 text-center">
            <x-ui.button href="{{ $button->url }}" :variant="$button->variant">{{ $button->label }}</x-ui.button>
        </div>
    @endforeach
</x-ui.section>
