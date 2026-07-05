@extends('layouts.app')

@section('title', 'Photo Gallery — '.config('app.name'))
@section('meta_description', 'Browse photo albums and videos from events, activities, and community programs run by '.config('app.name').'.')
@section('canonical_url', url('/gallery'))
@section('og_title', 'Photo Gallery — '.config('app.name'))
@section('og_description', 'Browse photo albums and videos from events and community programs run by '.config('app.name').'.')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Photo Gallery — '.config('app.name'),
            'url' => url('/gallery'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Gallery']]" class="mb-4" />

        <x-ui.section-heading heading="Photo Gallery" subheading="Moments from our events, camps, and community programs." />
    </x-ui.section>

    @if (empty($filters['q']) && empty($filters['category']) && $featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Albums</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($featured as $item)
                    <a href="{{ route('gallery.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-44 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->coverMedia()" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                @if ($item->category)
                                    <x-ui.badge variant="accent">{{ $item->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-2 font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">
                                    {{ $item->active_photos_count }} {{ Str::plural('photo', $item->active_photos_count) }}
                                    @if ($item->event_date)
                                        · {{ $item->event_date->format('d M Y') }}
                                    @endif
                                </p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        <form method="GET" action="{{ route('gallery.index') }}" class="mb-8 flex flex-wrap items-center gap-3">
            <div class="w-full max-w-xs">
                <x-ui.input name="q" placeholder="Search albums..." value="{{ $filters['q'] ?? '' }}" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>

            <div class="flex flex-wrap gap-2">
                <a
                    href="{{ route('gallery.index') }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium {{ empty($filters['category']) ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                >
                    All
                </a>
                @foreach ($categories as $category)
                    <a
                        href="{{ route('gallery.index', array_filter(['category' => $category->slug, 'q' => $filters['q'] ?? null])) }}"
                        class="rounded-full px-4 py-1.5 text-sm font-medium {{ ($filters['category'] ?? null) === $category->slug ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    >
                        {{ $category->name }}
                    </a>
                @endforeach
            </div>
        </form>

        @if ($albums->isEmpty())
            <x-ui.empty-state heading="No albums found" message="Try a different search term or category." />
        @else
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($albums as $album)
                    <a href="{{ route('gallery.show', $album) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="relative h-52 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$album->coverMedia()" :alt="$album->title" conversion="thumb" />
                                <span class="absolute bottom-2 right-2 rounded bg-black/60 px-2 py-0.5 text-xs font-medium text-white">
                                    {{ $album->active_photos_count }} {{ Str::plural('photo', $album->active_photos_count) }}
                                </span>
                            </div>
                            <div class="p-5">
                                @if ($album->category)
                                    <x-ui.badge variant="accent">{{ $album->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-3 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $album->title }}</p>
                                @if ($album->description)
                                    <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">{{ Str::limit($album->description, 110) }}</p>
                                @endif
                                <div class="mt-4 flex items-center gap-4 text-xs text-text-400 dark:text-night-text-muted">
                                    @if ($album->event_date)
                                        <span class="flex items-center gap-1"><x-ui.icon name="calendar" class="h-4 w-4" /> {{ $album->event_date->format('d M Y') }}</span>
                                    @endif
                                    @if ($album->location)
                                        <span class="flex items-center gap-1"><x-ui.icon name="map-pin" class="h-4 w-4" /> {{ $album->location }}</span>
                                    @endif
                                </div>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $albums->links() }}
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <x-ui.cta heading="Support Our Work" subheading="Every photo here is a moment your donation made possible." variant="dark">
                <x-ui.button href="{{ url('/donate') }}" variant="accent">Donate Now</x-ui.button>
            </x-ui.cta>
            <x-ui.cta heading="Be Part of the Next Album" subheading="Volunteer with us at our next event." variant="muted">
                <x-ui.button href="{{ url('/volunteer') }}" variant="secondary">Volunteer With Us</x-ui.button>
            </x-ui.cta>
        </div>
    </x-ui.section>
@endsection
