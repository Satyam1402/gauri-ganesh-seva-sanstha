@extends('layouts.app')

@php
    $pageUrl = route('faq.index');
    $isSearching = $filters['q'] !== '';
@endphp

@section('content')
    {{-- Hero + search --}}
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="$seo->breadcrumbItems()" class="mb-6" />

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">Help Centre</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">Frequently Asked Questions</h1>
            <p class="mt-4 text-base text-text-600 dark:text-night-text-muted">
                Everything you need to know about donating, volunteering, our programmes, and how to get support.
            </p>

            <form method="GET" action="{{ $pageUrl }}" role="search" class="mx-auto mt-8 flex max-w-xl flex-col gap-3 sm:flex-row">
                @if ($currentCategory)
                    <input type="hidden" name="category" value="{{ $currentCategory->slug }}">
                @endif
                <div class="flex-1">
                    <label for="faq-search" class="sr-only">Search questions</label>
                    <x-ui.input id="faq-search" name="q" type="search" placeholder="Search questions and answers…" value="{{ $filters['q'] }}" maxlength="100" autocomplete="off" />
                </div>
                <x-ui.button type="submit" variant="primary">Search</x-ui.button>
                @if ($isSearching)
                    <x-ui.button href="{{ $currentCategory ? route('faq.index', ['category' => $currentCategory->slug]) : $pageUrl }}" variant="ghost">Clear</x-ui.button>
                @endif
            </form>
        </div>
    </x-ui.section>

    {{-- Featured (browse-all only) --}}
    @if ($featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Most Asked</h2>
            <x-faq.accordion :faqs="$featured" id-prefix="featured" show-category class="mt-6" />
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        {{-- Category tabs --}}
        @if ($categories->isNotEmpty())
            <nav aria-label="Filter FAQs by category" class="mb-8 flex flex-wrap gap-2">
                <a
                    href="{{ $isSearching ? route('faq.index', ['q' => $filters['q']]) : $pageUrl }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentCategory === null ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    @if ($currentCategory === null) aria-current="page" @endif
                >
                    All
                </a>
                @foreach ($categories as $category)
                    @continue($category->faqs_count === 0)
                    <a
                        href="{{ route('faq.index', array_filter(['category' => $category->slug, 'q' => $filters['q'] ?: null])) }}"
                        class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentCategory?->is($category) ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                        @if ($currentCategory?->is($category)) aria-current="page" @endif
                    >
                        {{ $category->name }} <span class="opacity-75">({{ $category->faqs_count }})</span>
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($isSearching)
            <p class="mb-6 text-sm text-text-600 dark:text-night-text-muted" role="status">
                {{ $faqs->count() }} {{ Str::plural('result', $faqs->count()) }} for “{{ $filters['q'] }}”{{ $currentCategory ? ' in '.$currentCategory->name : '' }}
            </p>
        @endif

        @if ($faqs->isEmpty())
            <x-ui.empty-state
                heading="{{ $isSearching ? 'No matching questions' : 'No FAQs yet' }}"
                message="{{ $isSearching ? 'Try different words, or browse by category below. Still stuck? Contact us and we will help.' : 'Answers to common questions will appear here soon.' }}"
            >
                <x-slot:action>
                    <x-ui.button href="{{ route('contact') }}" variant="secondary" size="sm">Contact Us</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @elseif ($isSearching || $currentCategory)
            {{-- Flat list for search results / one category --}}
            @if ($currentCategory && ! $isSearching)
                <div class="mb-6">
                    <h2 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $currentCategory->name }}</h2>
                    @if ($currentCategory->description)
                        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $currentCategory->description }}</p>
                    @endif
                </div>
            @else
                <h2 class="sr-only">Search results</h2>
            @endif
            <x-faq.accordion :faqs="$faqs" :show-category="$isSearching && ! $currentCategory" />
        @else
            {{-- Browse: grouped by category --}}
            <div class="space-y-10">
                @foreach ($groups as $categoryId => $group)
                    @php $category = $categoryId ? $group->first()->category : null; @endphp
                    <section aria-labelledby="faq-group-{{ $categoryId }}">
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <h2 id="faq-group-{{ $categoryId }}" class="font-display text-xl font-semibold text-text-900 dark:text-night-text">{{ $category?->name ?? 'Other Questions' }}</h2>
                                @if ($category?->description)
                                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $category->description }}</p>
                                @endif
                            </div>
                            @if ($category)
                                <a href="{{ route('faq.index', ['category' => $category->slug]) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View category</a>
                            @endif
                        </div>
                        <x-faq.accordion :faqs="$group" />
                    </section>
                @endforeach
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <x-ui.cta heading="Still Have a Question?" subheading="Our team usually responds within 2–3 working days." variant="muted">
            <x-ui.button href="{{ route('contact') }}" variant="secondary">Contact Us</x-ui.button>
        </x-ui.cta>
    </x-ui.section>
@endsection
