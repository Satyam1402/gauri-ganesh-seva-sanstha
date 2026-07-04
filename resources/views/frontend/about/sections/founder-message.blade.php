<x-ui.section background="white">
    <div class="mx-auto grid max-w-4xl items-center gap-10 lg:grid-cols-3">
        <div class="mx-auto aspect-square w-40 overflow-hidden rounded-full bg-surface-muted lg:w-full">
            <x-ui.lazy-image :media="$section->getFirstMedia('image')" :alt="$section->subheading" />
        </div>

        <div class="text-center lg:col-span-2 lg:text-left">
            @if ($section->heading)
                <h2 class="font-display text-3xl font-semibold text-text-900">{{ $section->heading }}</h2>
            @endif
            @if ($section->description)
                <p class="mt-4 font-display text-xl text-text-600">&ldquo;{{ $section->description }}&rdquo;</p>
            @endif
            @if ($section->subheading)
                <p class="mt-4 font-semibold text-text-900">{{ $section->subheading }}</p>
            @endif
        </div>
    </div>
</x-ui.section>
