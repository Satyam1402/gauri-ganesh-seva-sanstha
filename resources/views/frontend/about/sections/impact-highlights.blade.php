<x-ui.section background="muted">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-2 gap-8 text-center lg:grid-cols-4">
            @foreach ($section->activeItems as $item)
                <div>
                    <p class="font-display text-4xl font-semibold text-primary-700">{{ $item->title }}</p>
                    <p class="mt-1 text-sm text-text-600">{{ $item->subtitle }}</p>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Impact numbers coming soon" class="mt-10" />
    @endif
</x-ui.section>
