{{--
    Homepage testimonials. Heading/subheading remain CMS-managed; the cards
    themselves now come from the Testimonials module (Phase 12) — featured
    entries first, falling back to the latest published ones.
--}}
<x-testimonials-section
    :heading="$section->heading"
    :subheading="$section->subheading"
    :featured="true"
    :limit="3"
    :hide-when-empty="false"
    background="muted"
/>
