{{-- Reusable partners block — data is resolved by App\View\Components\PartnersSection. --}}
<x-ui.section :background="$background" {{ $attributes }}>
    <x-ui.section-heading :heading="$heading" :subheading="$subheading" align="center" class="mx-auto" />

    @if ($items->isEmpty())
        <x-ui.empty-state heading="No partners listed yet" class="mx-auto mt-10 max-w-md" />
    @elseif ($variant === 'cards')
        <ul class="mt-10 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
            @foreach ($items as $partner)
                <li><x-partners.card :partner="$partner" /></li>
            @endforeach
        </ul>
    @else
        {{--
            Logo wall. Each tile links to the partner's detail page; the
            organisation name is always available to assistive tech through
            the logo's alt text (or as visible text when there is no logo).
        --}}
        <ul class="mt-10 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4" role="list">
            @foreach ($items as $partner)
                <li>
                    <a
                        href="{{ route('partners.show', $partner) }}"
                        class="group block rounded-lg focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35"
                        title="{{ $partner->name }}"
                    >
                        <x-partners.logo :partner="$partner" class="transition group-hover:border-primary-700/40 group-hover:shadow-sm" />
                    </a>
                </li>
            @endforeach
        </ul>
    @endif

    {{-- A slot (e.g. a CMS-managed button) replaces the default link. --}}
    @if ($slot->isNotEmpty())
        <div class="mt-10 text-center">{{ $slot }}</div>
    @elseif ($showLink)
        <div class="mt-10 text-center">
            <x-ui.button href="{{ $linkUrl }}" variant="ghost">See All Partners</x-ui.button>
        </div>
    @endif
</x-ui.section>
