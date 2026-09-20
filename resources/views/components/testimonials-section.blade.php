{{-- Reusable testimonials block — data is resolved by App\View\Components\TestimonialsSection. --}}
<x-ui.section :background="$background" {{ $attributes }}>
    <x-ui.section-heading :heading="$heading" :subheading="$subheading" align="center" class="mx-auto" />

    @if ($items->isEmpty())
        <x-ui.empty-state heading="No testimonials yet" message="Stories from the people we work with will appear here soon." class="mt-10" />
    @else
        {{--
            Mobile: horizontal snap carousel (pure CSS, no JS). md+: grid.
            The ARIA region + label lets screen readers announce the group.
        --}}
        <div
            role="region"
            aria-label="{{ $typeLabel ?? 'Testimonials' }}"
            class="mt-10 -mx-4 flex snap-x snap-mandatory gap-4 overflow-x-auto px-4 pb-4 md:mx-0 md:grid md:grid-cols-2 md:gap-6 md:overflow-visible md:px-0 md:pb-0 {{ $items->count() >= 3 ? 'lg:grid-cols-3' : '' }}"
        >
            @foreach ($items as $testimonial)
                <x-testimonials.card :testimonial="$testimonial" clamp class="w-[85%] shrink-0 snap-center sm:w-[70%] md:w-auto md:shrink" />
            @endforeach
        </div>
    @endif

    @if ($showLink)
        <div class="mt-10 text-center">
            <x-ui.button href="{{ route('testimonials.index') }}" variant="ghost">Read More Stories</x-ui.button>
        </div>
    @endif
</x-ui.section>
