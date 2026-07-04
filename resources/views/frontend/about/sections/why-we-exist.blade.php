<x-ui.section background="white">
    <div class="mx-auto max-w-3xl text-center">
        @if ($section->heading)
            <h2 class="font-display text-3xl font-semibold text-text-900">{{ $section->heading }}</h2>
        @endif
        @if ($section->description)
            <p class="mt-6 text-lg text-text-600">{{ $section->description }}</p>
        @endif
    </div>
</x-ui.section>
