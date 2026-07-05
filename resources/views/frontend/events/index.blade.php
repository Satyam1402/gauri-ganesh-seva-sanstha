@extends('layouts.app')

@section('title', 'Events — '.config('app.name'))
@section('meta_description', 'Upcoming food distributions, medical camps, awareness campaigns, and community events organised by '.config('app.name').'. Register to take part.')
@section('canonical_url', url('/events'))
@section('og_title', 'Events — '.config('app.name'))
@section('og_description', 'Upcoming community events organised by '.config('app.name').' — see the schedule and register to take part.')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Events — '.config('app.name'),
            'url' => url('/events'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@php
    $when = $filters['when'] ?? 'upcoming';
@endphp

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Events']]" class="mb-4" />

        <x-ui.section-heading heading="Our Events" subheading="Join us on the ground — from food drives to medical camps, there's always a way to take part." />
    </x-ui.section>

    @if ($when !== 'past' && empty($filters['q']) && empty($filters['category']) && $featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Events</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($featured as $item)
                    <a href="{{ route('events.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                @if ($item->category)
                                    <x-ui.badge variant="accent">{{ $item->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-2 font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ $item->dateRange() }}{{ $item->city ? ' · '.$item->city : '' }}</p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        {{-- Upcoming / Past tabs --}}
        <div class="mb-6 flex gap-1 border-b border-border-subtle dark:border-night-border">
            <a
                href="{{ route('events.index', array_filter(['q' => $filters['q'] ?? null, 'category' => $filters['category'] ?? null])) }}"
                class="border-b-2 px-4 py-2.5 text-sm font-medium {{ $when !== 'past' ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted' }}"
            >
                Upcoming Events
            </a>
            <a
                href="{{ route('events.index', array_filter(['when' => 'past', 'q' => $filters['q'] ?? null, 'category' => $filters['category'] ?? null])) }}"
                class="border-b-2 px-4 py-2.5 text-sm font-medium {{ $when === 'past' ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted' }}"
            >
                Past Events
            </a>
        </div>

        <form method="GET" action="{{ route('events.index') }}" class="mb-8 flex flex-wrap items-center gap-3">
            @if ($when === 'past')
                <input type="hidden" name="when" value="past">
            @endif

            <div class="w-full max-w-xs">
                <x-ui.input name="q" placeholder="Search events..." value="{{ $filters['q'] ?? '' }}" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('events.index', array_filter(['when' => $when === 'past' ? 'past' : null])) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ empty($filters['category']) ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                >
                    All
                </a>
                @foreach ($categories as $category)
                    <a
                        href="{{ route('events.index', array_filter(['category' => $category->slug, 'when' => $when === 'past' ? 'past' : null, 'q' => $filters['q'] ?? null])) }}"
                        class="rounded-full px-4 py-1.5 text-sm font-medium {{ ($filters['category'] ?? null) === $category->slug ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </form>

        @if ($events->isEmpty())
            <x-ui.empty-state
                heading="No {{ $when === 'past' ? 'past' : 'upcoming' }} events found"
                message="{{ $when === 'past' ? 'Try a different search term or category.' : 'New events are announced regularly — check back soon.' }}"
            />
        @else
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($events as $event)
                    <a href="{{ route('events.show', $event) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="relative h-48 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$event->getFirstMedia('featured_image')" :alt="$event->title" conversion="thumb" />
                                <span class="absolute left-3 top-3 rounded-md bg-white/95 px-2.5 py-1.5 text-center shadow dark:bg-night-surface">
                                    <span class="block font-display text-lg font-bold leading-none text-primary-700 dark:text-night-text">{{ $event->start_date->format('d') }}</span>
                                    <span class="block text-[10px] font-semibold uppercase text-text-600 dark:text-night-text-muted">{{ $event->start_date->format('M') }}</span>
                                </span>
                                @if ($event->status->value === 'cancelled')
                                    <span class="absolute right-3 top-3 rounded bg-error-600 px-2 py-0.5 text-xs font-semibold uppercase text-white">Cancelled</span>
                                @endif
                            </div>
                            <div class="p-5">
                                @if ($event->category)
                                    <x-ui.badge variant="accent">{{ $event->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-3 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $event->title }}</p>
                                <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">{{ Str::limit($event->short_description, 110) }}</p>
                                <div class="mt-4 space-y-1.5 text-xs text-text-400 dark:text-night-text-muted">
                                    <span class="flex items-center gap-1"><x-ui.icon name="calendar" class="h-4 w-4" /> {{ $event->dateRange() }}{{ $event->timeRange() ? ', '.$event->timeRange() : '' }}</span>
                                    @if ($event->locationLine())
                                        <span class="flex items-center gap-1"><x-ui.icon name="map-pin" class="h-4 w-4" /> {{ $event->locationLine() }}</span>
                                    @endif
                                </div>
                                @if ($event->isRegistrationOpen())
                                    <p class="mt-3 text-xs font-semibold text-success-600">
                                        Registration open{{ $event->spotsLeft() !== null ? ' · '.$event->spotsLeft().' '.Str::plural('spot', $event->spotsLeft()).' left' : '' }}
                                    </p>
                                @endif
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $events->links() }}
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <x-ui.cta heading="Support Our Events" subheading="Your donation funds every camp, drive, and distribution we run." variant="dark">
                <x-ui.button href="{{ url('/donate') }}" variant="accent">Donate Now</x-ui.button>
            </x-ui.cta>
            <x-ui.cta heading="Volunteer With Us" subheading="Events run on volunteer power — join the next one." variant="muted">
                <x-ui.button href="{{ url('/volunteer') }}" variant="secondary">Become a Volunteer</x-ui.button>
            </x-ui.cta>
        </div>
    </x-ui.section>
@endsection
