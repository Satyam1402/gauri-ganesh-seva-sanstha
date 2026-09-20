@extends('layouts.app')

@php $pageUrl = route('partners.index'); @endphp

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="$seo->breadcrumbItems()" class="mb-6" />

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">Stronger Together</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">Our Partners &amp; Sponsors</h1>
            <p class="mt-4 text-base text-text-600 dark:text-night-text-muted">
                Every drive, camp and classroom is made possible by the organisations that stand with us —
                with funding, expertise, supplies and their people.
            </p>
        </div>
    </x-ui.section>

    {{-- Featured (unfiltered only) --}}
    @if ($featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Partners</h2>
            <ul class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                @foreach ($featured as $partner)
                    <li><x-partners.card :partner="$partner" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        @if ($types->isNotEmpty())
            <nav aria-label="Filter partners by type" class="mb-8 flex flex-wrap gap-2">
                <a
                    href="{{ $pageUrl }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentType === null ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    @if ($currentType === null) aria-current="page" @endif
                >
                    All
                </a>
                @foreach ($types as $type)
                    @continue($type->partners_count === 0)
                    <a
                        href="{{ route('partners.index', ['type' => $type->slug]) }}"
                        class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentType?->is($type) ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                        @if ($currentType?->is($type)) aria-current="page" @endif
                    >
                        {{ $type->name }} <span class="opacity-75">({{ $type->partners_count }})</span>
                    </a>
                @endforeach
            </nav>
        @endif

        @if ($partners->isEmpty())
            <x-ui.empty-state
                heading="No partners listed yet"
                message="{{ $currentType ? 'No '.Str::lower($currentType->name).'s have been listed yet — try another type.' : 'Organisations that support our work will appear here soon.' }}"
            >
                <x-slot:action>
                    <x-ui.button href="{{ route('contact') }}" variant="secondary" size="sm">Partner With Us</x-ui.button>
                </x-slot:action>
            </x-ui.empty-state>
        @elseif ($currentType)
            <div class="mb-6">
                <h2 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $currentType->name }}s</h2>
                @if ($currentType->description)
                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $currentType->description }}</p>
                @endif
            </div>
            <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                @foreach ($partners as $partner)
                    <li><x-partners.card :partner="$partner" /></li>
                @endforeach
            </ul>
        @else
            <div class="space-y-12">
                @foreach ($groups as $typeId => $group)
                    @php $type = $typeId ? $group->first()->type : null; @endphp
                    <section aria-labelledby="partner-group-{{ $typeId }}">
                        <div class="mb-4 flex flex-wrap items-end justify-between gap-2">
                            <div>
                                <h2 id="partner-group-{{ $typeId }}" class="font-display text-xl font-semibold text-text-900 dark:text-night-text">{{ $type ? $type->name.'s' : 'Other Supporters' }}</h2>
                                @if ($type?->description)
                                    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $type->description }}</p>
                                @endif
                            </div>
                            @if ($type)
                                <a href="{{ route('partners.index', ['type' => $type->slug]) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View all {{ Str::lower($type->name) }}s</a>
                            @endif
                        </div>
                        <ul class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                            @foreach ($group as $partner)
                                <li><x-partners.card :partner="$partner" /></li>
                            @endforeach
                        </ul>
                    </section>
                @endforeach
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <x-ui.cta heading="Partner With Us" subheading="CSR programmes, in-kind support, media partnerships — let's talk about how your organisation can make a difference." variant="dark">
            <x-ui.button href="{{ route('contact') }}" variant="accent">Get in Touch</x-ui.button>
        </x-ui.cta>
    </x-ui.section>
@endsection
