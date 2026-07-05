@extends('layouts.app')

@php
    $seo = $activity->seo;
    $shareUrl = route('activities.show', $activity);
    $metaDescription = $seo?->meta_description ?? $activity->short_description;
@endphp

@section('title', $seo?->meta_title ?? $activity->title.' — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $shareUrl)
@section('og_title', $seo?->og_title ?? $activity->title)
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage ?? $activity->getFirstMedia('featured_image'))
    @section('og_image', ($seo?->ogImage ?? $activity->getFirstMedia('featured_image'))->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'Event',
            'name' => $activity->title,
            'description' => $metaDescription,
            'startDate' => $activity->activity_date->toDateString(),
            'image' => $activity->getFirstMedia('featured_image')?->getUrl(),
            'location' => $activity->location ? [
                '@type' => 'Place',
                'name' => $activity->location,
            ] : null,
            'organizer' => $activity->organizer ? [
                '@type' => 'Organization',
                'name' => $activity->organizer,
            ] : null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Activities', 'url' => route('activities.index')],
            ...($activity->category ? [['label' => $activity->category->name, 'url' => route('activities.index', ['category' => $activity->category->slug])]] : []),
            ['label' => $activity->title],
        ]" class="mb-6" />

        <div class="h-72 w-full overflow-hidden rounded-xl sm:h-96">
            <x-ui.lazy-image :media="$activity->getFirstMedia('featured_image')" :alt="$activity->title" conversion="webp" />
        </div>

        <div class="mt-6">
            @if ($activity->category)
                <x-ui.badge variant="accent">{{ $activity->category->name }}</x-ui.badge>
            @endif

            <h1 class="mt-3 font-display text-3xl font-semibold text-text-900 dark:text-night-text">{{ $activity->title }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-text-600 dark:text-night-text-muted">
                <span class="flex items-center gap-1.5"><x-ui.icon name="calendar" class="h-5 w-5 text-primary-700" /> {{ $activity->activity_date->format('d M Y') }}</span>
                @if ($activity->location)
                    <span class="flex items-center gap-1.5"><x-ui.icon name="map-pin" class="h-5 w-5 text-primary-700" /> {{ $activity->location }}</span>
                @endif
                @if ($activity->organizer)
                    <span class="flex items-center gap-1.5"><x-ui.icon name="users" class="h-5 w-5 text-primary-700" /> {{ $activity->organizer }}</span>
                @endif
            </div>
        </div>
    </x-ui.section>

    <x-ui.section background="base" spacing="sm">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="prose max-w-none lg:col-span-2 dark:prose-invert">
                {!! nl2br(e($activity->full_description)) !!}
            </div>

            <aside class="space-y-6">
                <x-ui.card>
                    <p class="text-sm font-semibold text-text-900 dark:text-night-text">Share This Activity</p>
                    <div class="mt-3 flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on Facebook">
                            <x-ui.icon name="facebook" class="h-4 w-4" />
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($activity->title) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on X">
                            <x-ui.icon name="x-twitter" class="h-4 w-4" />
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($activity->title.' — '.$shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on WhatsApp">
                            <x-ui.icon name="whatsapp" class="h-4 w-4" />
                        </a>
                        <button
                            type="button"
                            onclick="navigator.clipboard.writeText('{{ $shareUrl }}'); this.querySelector('span').textContent = 'Copied!'; setTimeout(() => this.querySelector('span').textContent = '', 1500);"
                            class="relative flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted"
                            aria-label="Copy link"
                        >
                            <x-ui.icon name="link" class="h-4 w-4" />
                            <span class="absolute -top-7 left-1/2 -translate-x-1/2 rounded bg-primary-800 px-2 py-0.5 text-xs text-white empty:hidden"></span>
                        </button>
                    </div>
                </x-ui.card>

                <x-ui.cta heading="Support This Cause" variant="dark">
                    <x-ui.button href="{{ url('/donate') }}" variant="accent" size="sm">Donate Now</x-ui.button>
                </x-ui.cta>
                <x-ui.cta heading="Volunteer With Us" variant="muted">
                    <x-ui.button href="{{ url('/volunteer') }}" variant="secondary" size="sm">Get Involved</x-ui.button>
                </x-ui.cta>
            </aside>
        </div>

        @if ($activity->getMedia('gallery')->isNotEmpty())
            <div class="mt-12">
                <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Gallery</h2>
                <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                    @foreach ($activity->getMedia('gallery') as $media)
                        <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block h-32 overflow-hidden rounded-lg">
                            <x-ui.lazy-image :media="$media" :alt="$activity->title" />
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </x-ui.section>

    @if ($related->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Related Activities</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('activities.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ $item->activity_date->format('d M Y') }}</p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif
@endsection
