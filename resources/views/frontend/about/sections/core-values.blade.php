<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($section->activeItems as $item)
                <div class="flex flex-col items-center text-center">
                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-night-surface-alt">
                        <x-ui.icon :name="$item->icon" class="text-primary-700" />
                    </span>
                    <h3 class="mt-4 font-semibold text-text-900">{{ $item->title }}</h3>
                    <p class="mt-2 text-sm text-text-600">{{ $item->description }}</p>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Core values coming soon" class="mt-10" />
    @endif
</x-ui.section>
