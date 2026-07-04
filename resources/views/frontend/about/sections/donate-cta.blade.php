<x-ui.section background="muted">
    <x-ui.cta :heading="$section->heading" :subheading="$section->subheading" variant="dark">
        @foreach ($section->buttons as $button)
            <x-ui.button href="{{ $button->url }}" :variant="$button->variant" size="lg">{{ $button->label }}</x-ui.button>
        @endforeach
    </x-ui.cta>
</x-ui.section>
