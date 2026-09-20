{{-- One partner card: logo box, name (always text), type badge, description, website link. --}}
@props(['partner', 'showDescription' => true])

<article {{ $attributes->class(['flex h-full flex-col rounded-lg border border-border-subtle bg-surface-white p-4 shadow-sm dark:border-night-border dark:bg-night-surface']) }}>
    <a href="{{ route('partners.show', $partner) }}" class="block rounded-lg focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35" aria-label="{{ $partner->name }} — details">
        <x-partners.logo :partner="$partner" size="sm" class="bg-surface-muted dark:bg-night-surface-alt" />
    </a>

    <div class="mt-4 flex flex-1 flex-col">
        <h3 class="font-display text-base font-semibold text-text-900 dark:text-night-text">
            <a href="{{ route('partners.show', $partner) }}" class="hover:underline focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35">{{ $partner->name }}</a>
        </h3>

        <div class="mt-1 flex flex-wrap items-center gap-2 text-xs text-text-400 dark:text-night-text-muted">
            @if ($partner->type)
                <x-ui.badge variant="neutral">{{ $partner->type->name }}</x-ui.badge>
            @endif
            @if ($partner->partnershipPeriod())
                <span>{{ $partner->partnershipPeriod() }}</span>
            @endif
        </div>

        @if ($showDescription && $partner->short_description)
            <p class="mt-2 line-clamp-3 text-sm text-text-600 dark:text-night-text-muted">{{ $partner->short_description }}</p>
        @endif

        @if ($partner->website_url)
            <a
                href="{{ $partner->website_url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="mt-auto inline-flex items-center gap-1 pt-3 text-sm font-medium text-primary-700 hover:underline focus:outline-none focus-visible:ring-3 focus-visible:ring-primary-700/35 dark:text-night-text"
            >
                {{ $partner->websiteHost() ?? 'Visit website' }}
                <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" /></svg>
                <span class="sr-only">(opens in a new tab)</span>
            </a>
        @endif
    </div>
</article>
