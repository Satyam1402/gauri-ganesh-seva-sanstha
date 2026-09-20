{{--
    Homepage partners logo wall. Heading/subheading remain CMS-managed; the
    logos now come from the Partners module (Phase 14) — featured entries
    first, falling back to any active partners.
--}}
<x-partners-section
    :heading="$section->heading"
    :subheading="$section->subheading"
    :featured="true"
    :limit="8"
    :hide-when-empty="false"
    variant="logos"
    background="white"
/>
