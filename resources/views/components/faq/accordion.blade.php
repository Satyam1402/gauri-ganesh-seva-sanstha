{{--
    Accessible FAQ accordion. One Alpine scope per list holds the open id;
    each header is a real <button> with aria-expanded/aria-controls, and the
    panel is a labelled region. Arrow/Home/End keys move between headers
    (WAI-ARIA accordion pattern); Enter/Space toggle natively.

    $faqs: iterable of App\Models\Faq. $idPrefix keeps ids unique when
    several accordions share a page. $showCategory adds a category badge.
--}}
@props(['faqs', 'idPrefix' => 'faq', 'showCategory' => false, 'openFirst' => false])

<div
    x-data="{
        open: {{ $openFirst && count($faqs) ? (int) $faqs->first()->id : 'null' }},
        toggle(id) { this.open = this.open === id ? null : id; },
        headers() { return Array.from($el.querySelectorAll('[data-faq-header]')); },
        move(event, delta) {
            const items = this.headers();
            const index = items.indexOf(event.target);
            if (index === -1) return;
            const next = delta === 'first' ? 0 : delta === 'last' ? items.length - 1 : (index + delta + items.length) % items.length;
            items[next].focus();
        },
    }"
    x-init="
        {{-- Deep links (#faq-12) open and scroll to that question. --}}
        const match = window.location.hash.match(/^#{{ $idPrefix }}-(\d+)$/);
        if (match) { open = parseInt(match[1]); $nextTick(() => document.getElementById(window.location.hash.slice(1))?.scrollIntoView({ block: 'start' })); }
    "
    {{ $attributes->class(['divide-y divide-border-subtle rounded-lg border border-border-subtle bg-surface-white dark:divide-night-border dark:border-night-border dark:bg-night-surface']) }}
>
    @foreach ($faqs as $faq)
        @php
            $headerId = "{$idPrefix}-{$faq->id}-header";
            $panelId = "{$idPrefix}-{$faq->id}-panel";
        @endphp
        <div id="{{ $idPrefix }}-{{ $faq->id }}" class="scroll-mt-24">
            <h3 class="m-0 text-base font-sans font-medium leading-normal">
                <button
                    type="button"
                    id="{{ $headerId }}"
                    data-faq-header
                    aria-controls="{{ $panelId }}"
                    :aria-expanded="open === {{ $faq->id }} ? 'true' : 'false'"
                    @click="toggle({{ $faq->id }})"
                    @keydown.down.prevent="move($event, 1)"
                    @keydown.up.prevent="move($event, -1)"
                    @keydown.home.prevent="move($event, 'first')"
                    @keydown.end.prevent="move($event, 'last')"
                    class="flex w-full items-start justify-between gap-4 px-5 py-4 text-left text-text-900 hover:bg-surface-muted focus:outline-none focus-visible:ring-3 focus-visible:ring-inset focus-visible:ring-primary-700/35 dark:text-night-text dark:hover:bg-night-surface-alt"
                >
                    <span>
                        {{ $faq->question }}
                        @if ($showCategory && $faq->category)
                            <x-ui.badge variant="neutral" class="ml-2 align-middle">{{ $faq->category->name }}</x-ui.badge>
                        @endif
                    </span>
                    <svg xmlns="http://www.w3.org/2000/svg" aria-hidden="true" class="mt-1 h-4 w-4 shrink-0 text-primary-700 transition-transform duration-200 dark:text-night-text" :class="{ 'rotate-180': open === {{ $faq->id }} }" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                    </svg>
                </button>
            </h3>
            <div
                id="{{ $panelId }}"
                role="region"
                aria-labelledby="{{ $headerId }}"
                x-show="open === {{ $faq->id }}"
                x-transition.opacity.duration.150ms
                x-cloak
            >
                <div class="prose px-5 pb-5 text-sm text-text-600 dark:text-night-text-muted">
                    {!! $faq->answerHtml() !!}
                </div>
            </div>
        </div>
    @endforeach
</div>
