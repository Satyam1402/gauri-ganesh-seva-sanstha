<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-5">
            @foreach ($section->activeItems as $item)
                <x-ui.card hoverable class="text-center">
                    <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-primary-100 dark:bg-night-surface-alt">
                        <x-ui.icon :name="$item->icon" class="text-primary-700" />
                    </span>
                    <h3 class="mt-4 font-display text-lg font-semibold text-text-900">{{ $item->title }}</h3>
                    <p class="mt-2 text-sm text-text-600">{{ $item->description }}</p>
                </x-ui.card>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="Programs coming soon" class="mt-10" />
    @endif

    @foreach ($section->buttons as $button)
        <div class="mt-10 text-center">
            <x-ui.button href="{{ $button->url }}" :variant="$button->variant">{{ $button->label }}</x-ui.button>
        </div>
    @endforeach
</x-ui.section>
