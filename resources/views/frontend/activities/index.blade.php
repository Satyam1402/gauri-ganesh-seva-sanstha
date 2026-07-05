@extends('layouts.app')

@section('title', 'Our Activities — '.config('app.name'))
@section('meta_description', 'Explore food distributions, medical camps, education support, and other community programs run by '.config('app.name').'.')
@section('canonical_url', url('/activities'))
@section('og_title', 'Our Activities — '.config('app.name'))
@section('og_description', 'Explore the community programs and outreach activities run by '.config('app.name').'.')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Our Activities — '.config('app.name'),
            'url' => url('/activities'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Activities']]" class="mb-4" />

        <x-ui.section-heading heading="Our Activities" subheading="Real programs, real impact — see what we've been doing in the community." />
    </x-ui.section>

    @if (empty($filters['q']) && empty($filters['category']) && $latest->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Latest Activities</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($latest as $item)
                    <a href="{{ route('activities.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full p-0 overflow-hidden">
                            <div class="h-40 w-full overflow-hidden">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" />
                            </div>
                            <div class="p-4">
                                <x-ui.badge variant="accent">{{ $item->category?->name }}</x-ui.badge>
                                <p class="mt-2 font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ $item->activity_date->format('d M Y') }}</p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        <form method="GET" action="{{ route('activities.index') }}" class="mb-8 flex flex-wrap items-center gap-3">
            <div class="w-full max-w-xs">
                <x-ui.input name="q" placeholder="Search activities..." value="{{ $filters['q'] ?? '' }}" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('activities.index') }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ empty($filters['category']) ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                >
                    All
                </a>
                @foreach ($categories as $category)
                    <a
                        href="{{ route('activities.index', array_filter(['category' => $category->slug, 'q' => $filters['q'] ?? null])) }}"
                        class="rounded-full px-4 py-1.5 text-sm font-medium {{ ($filters['category'] ?? null) === $category->slug ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </form>

        @if ($activities->isEmpty())
            <x-ui.empty-state heading="No activities found" message="Try a different search term or category." />
        @else
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($activities as $activity)
                    <a href="{{ route('activities.show', $activity) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-48 w-full overflow-hidden">
                                <x-ui.lazy-image :media="$activity->getFirstMedia('featured_image')" :alt="$activity->title" />
                            </div>
                            <div class="p-5">
                                @if ($activity->category)
                                    <x-ui.badge variant="accent">{{ $activity->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-3 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $activity->title }}</p>
                                <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">{{ Str::limit($activity->short_description, 110) }}</p>
                                <div class="mt-4 flex items-center gap-4 text-xs text-text-400 dark:text-night-text-muted">
                                    <span class="flex items-center gap-1"><x-ui.icon name="calendar" class="h-4 w-4" /> {{ $activity->activity_date->format('d M Y') }}</span>
                                    @if ($activity->location)
                                        <span class="flex items-center gap-1"><x-ui.icon name="map-pin" class="h-4 w-4" /> {{ $activity->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $activities->links() }}
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <x-ui.cta heading="Support Our Work" subheading="Your donation helps fund every program you see here." variant="dark">
                <x-ui.button href="{{ url('/donate') }}" variant="accent">Donate Now</x-ui.button>
            </x-ui.cta>
            <x-ui.cta heading="Become a Volunteer" subheading="Join us on the ground for our next activity." variant="muted">
                <x-ui.button href="{{ url('/volunteer') }}" variant="secondary">Volunteer With Us</x-ui.button>
            </x-ui.cta>
        </div>
    </x-ui.section>
@endsection
