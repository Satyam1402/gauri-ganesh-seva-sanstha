{{--
    Homepage FAQ preview. Heading/subheading and the CTA button remain
    CMS-managed; the questions now come from the FAQ module (Phase 13) —
    featured entries first, falling back to the latest published ones.
--}}
<x-faq-section
    :heading="$section->heading"
    :subheading="$section->subheading"
    :featured="true"
    :limit="5"
    :hide-when-empty="false"
    background="white"
    id-prefix="home-faq"
>
    @foreach ($section->buttons as $button)
        <x-ui.button href="{{ $button->url }}" :variant="$button->variant">{{ $button->label }}</x-ui.button>
    @endforeach
</x-faq-section>
