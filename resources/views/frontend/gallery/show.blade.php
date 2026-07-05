@extends('layouts.app')

@php
    $seo = $album->seo;
    $shareUrl = route('gallery.show', $album);
    $metaDescription = $seo?->meta_description
        ?? ($album->description ? Str::limit($album->description, 160) : 'Photo album from '.config('app.name').': '.$album->title);
    $cover = $album->coverMedia();
@endphp

@section('title', $seo?->meta_title ?? $album->title.' — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $shareUrl)
@section('og_title', $seo?->og_title ?? $album->title)
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage ?? $cover)
    @section('og_image', ($seo?->ogImage ?? $cover)->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'ImageGallery',
            'name' => $album->title,
            'description' => $metaDescription,
            'url' => $shareUrl,
            'image' => $cover?->getUrl(),
            'datePublished' => $album->event_date?->toDateString(),
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Gallery', 'url' => route('gallery.index')],
            ...($album->category ? [['label' => $album->category->name, 'url' => route('gallery.index', ['category' => $album->category->slug])]] : []),
            ['label' => $album->title],
        ]" class="mb-6" />

        @if ($album->category)
            <x-ui.badge variant="accent">{{ $album->category->name }}</x-ui.badge>
        @endif

        <h1 class="mt-3 font-display text-3xl font-semibold text-text-900 dark:text-night-text">{{ $album->title }}</h1>

        <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-text-600 dark:text-night-text-muted">
            @if ($album->event_date)
                <span class="flex items-center gap-1.5"><x-ui.icon name="calendar" class="h-5 w-5 text-primary-700" /> {{ $album->event_date->format('d M Y') }}</span>
            @endif
            @if ($album->location)
                <span class="flex items-center gap-1.5"><x-ui.icon name="map-pin" class="h-5 w-5 text-primary-700" /> {{ $album->location }}</span>
            @endif
            <span>{{ $photos->count() }} {{ Str::plural('photo', $photos->count()) }}@if ($videos->isNotEmpty()), {{ $videos->count() }} {{ Str::plural('video', $videos->count()) }}@endif</span>
        </div>

        @if ($album->description)
            <p class="mt-4 max-w-3xl text-text-600 dark:text-night-text-muted">{{ $album->description }}</p>
        @endif
    </x-ui.section>

    <x-ui.section background="base" spacing="sm">
        @if ($photos->isEmpty() && $videos->isEmpty())
            <x-ui.empty-state heading="Nothing here yet" message="Photos from this event will be published soon." />
        @endif

        @if ($photos->isNotEmpty())
            @php
                $lightboxItems = $photos->map(fn ($photo) => [
                    'src' => $photo->getFirstMedia('image')?->hasGeneratedConversion('webp')
                        ? $photo->getFirstMedia('image')->getUrl('webp')
                        : $photo->getFirstMedia('image')?->getUrl(),
                    'caption' => $photo->caption,
                    'alt' => $photo->resolvedAltText(),
                    'photographer' => $photo->photographer,
                ])->values();
            @endphp

            <div
                x-data="{
                    open: false,
                    index: 0,
                    items: {{ Js::from($lightboxItems) }},
                    show(i) { this.index = i; this.open = true; document.body.style.overflow = 'hidden'; },
                    close() { this.open = false; document.body.style.overflow = ''; },
                    next() { this.index = (this.index + 1) % this.items.length; },
                    prev() { this.index = (this.index - 1 + this.items.length) % this.items.length; },
                }"
                @keydown.escape.window="close()"
                @keydown.arrow-right.window="open && next()"
                @keydown.arrow-left.window="open && prev()"
            >
                <div class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($photos as $i => $photo)
                        <button
                            type="button"
                            @click="show({{ $i }})"
                            class="group block h-40 w-full overflow-hidden rounded-lg bg-surface-muted focus:outline-none focus:ring-3 focus:ring-primary-700/35 sm:h-48 dark:bg-night-surface-alt"
                            aria-label="View photo {{ $i + 1 }}{{ $photo->caption ? ': '.$photo->caption : '' }}"
                        >
                            <x-ui.lazy-image :media="$photo->getFirstMedia('image')" :alt="$photo->resolvedAltText()" conversion="thumb" class="transition duration-200 group-hover:scale-105" />
                        </button>
                    @endforeach
                </div>

                {{-- Lightbox overlay --}}
                <div
                    x-show="open"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                    role="dialog"
                    aria-modal="true"
                    aria-label="Photo viewer"
                    @click.self="close()"
                >
                    <button type="button" @click="close()" class="absolute right-4 top-4 z-10 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-2xl leading-none text-white hover:bg-white/20" aria-label="Close">
                        &times;
                    </button>

                    <button type="button" x-show="items.length > 1" @click.stop="prev()" class="absolute left-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:left-6" aria-label="Previous photo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" /></svg>
                    </button>
                    <button type="button" x-show="items.length > 1" @click.stop="next()" class="absolute right-2 top-1/2 z-10 flex h-11 w-11 -translate-y-1/2 items-center justify-center rounded-full bg-white/10 text-white hover:bg-white/20 sm:right-6" aria-label="Next photo">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="h-6 w-6"><path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" /></svg>
                    </button>

                    <figure class="flex max-h-full max-w-5xl flex-col items-center" @click.stop>
                        <img :src="items[index]?.src" :alt="items[index]?.alt" class="max-h-[80vh] w-auto max-w-full rounded-lg object-contain">
                        <figcaption class="mt-3 text-center text-sm text-white/80">
                            <span x-text="items[index]?.caption"></span>
                            <span x-show="items[index]?.photographer" class="text-white/50"> — 📷 <span x-text="items[index]?.photographer"></span></span>
                            <span class="ml-2 text-white/50" x-text="(index + 1) + ' / ' + items.length"></span>
                        </figcaption>
                    </figure>
                </div>
            </div>
        @endif

        @if ($videos->isNotEmpty())
            <div class="{{ $photos->isNotEmpty() ? 'mt-12' : '' }}">
                <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Videos</h2>
                <div class="mt-5 grid grid-cols-1 gap-6 sm:grid-cols-2">
                    @foreach ($videos as $video)
                        <div>
                            <div class="aspect-video w-full overflow-hidden rounded-lg bg-text-900">
                                @if ($video->provider->value === 'self_hosted')
                                    <video controls preload="metadata" class="h-full w-full" @if ($video->thumbnailUrl()) poster="{{ $video->thumbnailUrl() }}" @endif>
                                        <source src="{{ $video->embedUrl() }}">
                                        Your browser does not support the video tag.
                                    </video>
                                @elseif ($video->embedUrl())
                                    <iframe
                                        src="{{ $video->embedUrl() }}"
                                        title="{{ $video->title }}"
                                        class="h-full w-full"
                                        loading="lazy"
                                        allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                                        allowfullscreen
                                        referrerpolicy="strict-origin-when-cross-origin"
                                    ></iframe>
                                @endif
                            </div>
                            <p class="mt-2 font-medium text-text-900 dark:text-night-text">{{ $video->title }}</p>
                            @if ($video->description)
                                <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">{{ $video->description }}</p>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Share --}}
        <div class="mt-12 flex flex-wrap items-center gap-3">
            <span class="text-sm font-semibold text-text-900 dark:text-night-text">Share this album:</span>
            <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on Facebook">
                <x-ui.icon name="facebook" class="h-4 w-4" />
            </a>
            <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($album->title) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on X">
                <x-ui.icon name="x-twitter" class="h-4 w-4" />
            </a>
            <a href="https://wa.me/?text={{ urlencode($album->title.' — '.$shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on WhatsApp">
                <x-ui.icon name="whatsapp" class="h-4 w-4" />
            </a>
        </div>
    </x-ui.section>

    @if ($related->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">More Albums</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('gallery.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->coverMedia()" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
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
@endsection
