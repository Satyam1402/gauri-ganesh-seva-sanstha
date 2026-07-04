<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-1 gap-6 lg:grid-cols-3">
            @foreach ($section->activeItems as $item)
                <x-ui.card>
                    <p class="text-text-600">&ldquo;{{ $item->description }}&rdquo;</p>
                    <div class="mt-4 flex items-center gap-3">
                        <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-surface-muted">
                            <x-ui.lazy-image :media="$item->getFirstMedia('image')" :alt="$item->title" />
                        </div>
                        <div>
                            <p class="font-semibold text-text-900">{{ $item->title }}</p>
                            @if ($item->subtitle)
                                <x-ui.badge variant="neutral">{{ $item->subtitle }}</x-ui.badge>
                            @endif
                        </div>
                    </div>
                </x-ui.card>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="No testimonials yet" class="mt-10" />
    @endif
</x-ui.section>
