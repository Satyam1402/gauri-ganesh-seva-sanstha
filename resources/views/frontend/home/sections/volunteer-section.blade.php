<x-ui.section background="base">
    <div class="grid items-center gap-10 lg:grid-cols-2">
        <div class="order-2 lg:order-1">
            @if ($section->heading)
                <h2 class="font-display text-3xl font-semibold text-text-900">{{ $section->heading }}</h2>
            @endif
            @if ($section->subheading)
                <p class="mt-3 text-lg text-text-600">{{ $section->subheading }}</p>
            @endif

            @foreach ($section->buttons as $button)
                <x-ui.button href="{{ $button->url }}" :variant="$button->variant" class="mt-6">{{ $button->label }}</x-ui.button>
            @endforeach
        </div>

        <div class="order-1 aspect-[4/3] overflow-hidden rounded-xl bg-surface-muted lg:order-2">
            <x-ui.lazy-image :media="$section->getFirstMedia('image')" :alt="$section->heading" />
        </div>
    </div>
</x-ui.section>
