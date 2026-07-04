<x-ui.section background="white">
    <x-ui.section-heading :heading="$section->heading" :subheading="$section->subheading" align="center" class="mx-auto" />

    @if ($section->activeItems->isNotEmpty())
        <div class="mx-auto mt-10 max-w-2xl divide-y divide-border-subtle rounded-lg border border-border-subtle dark:divide-night-border dark:border-night-border">
            @foreach ($section->activeItems as $item)
                <div x-data="{ open: false }" class="p-4">
                    <button type="button" @click="open = ! open" class="flex w-full items-center justify-between gap-4 text-left font-medium text-text-900">
                        {{ $item->title }}
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                        </svg>
                    </button>
                    <p x-show="open" x-transition x-cloak class="mt-2 text-sm text-text-600">{{ $item->description }}</p>
                </div>
            @endforeach
        </div>
    @else
        <x-ui.empty-state heading="FAQs coming soon" class="mx-auto mt-10 max-w-md" />
    @endif

    @foreach ($section->buttons as $button)
        <div class="mt-8 text-center">
            <x-ui.button href="{{ $button->url }}" :variant="$button->variant">{{ $button->label }}</x-ui.button>
        </div>
    @endforeach
</x-ui.section>
