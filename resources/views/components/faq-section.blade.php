{{-- Reusable FAQ block — data is resolved by App\View\Components\FaqSection. --}}
<x-ui.section :background="$background" {{ $attributes }}>
    <x-ui.section-heading :heading="$heading" :subheading="$subheading" align="center" class="mx-auto" />

    @if ($items->isEmpty())
        <x-ui.empty-state heading="FAQs coming soon" class="mx-auto mt-10 max-w-md" />
    @else
        <x-faq.accordion :faqs="$items" :id-prefix="$idPrefix" class="mx-auto mt-10 max-w-3xl" />
    @endif

    {{-- A slot (e.g. a CMS-managed button) replaces the default link. --}}
    @if ($slot->isNotEmpty())
        <div class="mt-8 text-center">{{ $slot }}</div>
    @elseif ($showLink)
        <div class="mt-8 text-center">
            <x-ui.button href="{{ $linkUrl }}" variant="ghost">See All FAQs</x-ui.button>
        </div>
    @endif
</x-ui.section>
