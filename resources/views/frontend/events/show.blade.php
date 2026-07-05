@extends('layouts.app')

@php
    $seo = $event->seo;
    $shareUrl = route('events.show', $event);
    $metaDescription = $seo?->meta_description ?? ($event->short_description ?: 'Event by '.config('app.name').': '.$event->title);
    $startIso = $event->start_time
        ? $event->start_date->format('Y-m-d').'T'.\Carbon\Carbon::parse($event->start_time)->format('H:i:s')
        : $event->start_date->toDateString();
    $endIso = ($event->end_date ?? $event->start_date)->format('Y-m-d').($event->end_time ? 'T'.\Carbon\Carbon::parse($event->end_time)->format('H:i:s') : '');
    $isCancelled = $event->status->value === 'cancelled';
@endphp

@section('title', $seo?->meta_title ?? $event->title.' — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $shareUrl)
@section('og_title', $seo?->og_title ?? $event->title)
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage ?? $event->getFirstMedia('featured_image'))
    @section('og_image', ($seo?->ogImage ?? $event->getFirstMedia('featured_image'))->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'Event',
            'name' => $event->title,
            'description' => $metaDescription,
            'url' => $shareUrl,
            'startDate' => $startIso,
            'endDate' => $endIso ?: null,
            'eventStatus' => $isCancelled ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'image' => $event->getFirstMedia('featured_image')?->getUrl(),
            'location' => $event->venue || $event->city ? [
                '@type' => 'Place',
                'name' => $event->venue ?? $event->city,
                'address' => array_filter([
                    '@type' => 'PostalAddress',
                    'streetAddress' => $event->address,
                    'addressLocality' => $event->city,
                    'addressRegion' => $event->state,
                    'addressCountry' => 'IN',
                ]),
            ] : null,
            'organizer' => $event->organizer ? [
                '@type' => 'Organization',
                'name' => $event->organizer,
            ] : null,
            'maximumAttendeeCapacity' => $event->max_participants,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Events', 'url' => route('events.index')],
            ...($event->category ? [['label' => $event->category->name, 'url' => route('events.index', ['category' => $event->category->slug])]] : []),
            ['label' => $event->title],
        ]" class="mb-6" />

        @if ($isCancelled)
            <div class="mb-6 rounded-md border border-error-600/30 bg-red-50 px-4 py-3 text-sm font-medium text-error-600 dark:bg-night-surface-alt">
                This event has been cancelled. Registrations are closed — we apologise for any inconvenience.
            </div>
        @endif

        <div class="h-72 w-full overflow-hidden rounded-xl sm:h-96">
            <x-ui.lazy-image :media="$event->getFirstMedia('featured_image')" :alt="$event->title" conversion="webp" />
        </div>

        <div class="mt-6">
            @if ($event->category)
                <x-ui.badge variant="accent">{{ $event->category->name }}</x-ui.badge>
            @endif
            @if ($event->isPast() && ! $isCancelled)
                <x-ui.badge variant="neutral">Past Event</x-ui.badge>
            @endif

            <h1 class="mt-3 font-display text-3xl font-semibold text-text-900 dark:text-night-text">{{ $event->title }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-text-600 dark:text-night-text-muted">
                <span class="flex items-center gap-1.5"><x-ui.icon name="calendar" class="h-5 w-5 text-primary-700" /> {{ $event->dateRange() }}</span>
                @if ($event->timeRange())
                    <span class="flex items-center gap-1.5"><x-ui.icon name="clock" class="h-5 w-5 text-primary-700" /> {{ $event->timeRange() }}</span>
                @endif
                @if ($event->locationLine())
                    <span class="flex items-center gap-1.5"><x-ui.icon name="map-pin" class="h-5 w-5 text-primary-700" /> {{ $event->locationLine() }}</span>
                @endif
                @if ($event->organizer)
                    <span class="flex items-center gap-1.5"><x-ui.icon name="users" class="h-5 w-5 text-primary-700" /> {{ $event->organizer }}</span>
                @endif
            </div>
        </div>
    </x-ui.section>

    <x-ui.section background="base" spacing="sm">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="prose max-w-none dark:prose-invert">
                    {!! nl2br(e($event->full_description)) !!}
                </div>

                @if ($event->address || $event->map_url)
                    <div class="mt-10">
                        <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Location</h2>
                        @if ($event->address)
                            <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">{{ $event->address }}{{ $event->city ? ', '.$event->city : '' }}{{ $event->state ? ', '.$event->state : '' }}</p>
                        @endif
                        @if ($event->map_url)
                            @if (Str::contains($event->map_url, '/maps/embed'))
                                <div class="mt-4 aspect-video w-full overflow-hidden rounded-lg">
                                    <iframe src="{{ $event->map_url }}" class="h-full w-full border-0" loading="lazy" allowfullscreen referrerpolicy="no-referrer-when-downgrade" title="Map: {{ $event->title }}"></iframe>
                                </div>
                            @else
                                <a href="{{ $event->map_url }}" target="_blank" rel="noopener" class="mt-3 inline-flex items-center gap-1.5 text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                                    <x-ui.icon name="map-pin" class="h-4 w-4" /> View on Google Maps ↗
                                </a>
                            @endif
                        @endif
                    </div>
                @endif

                @if ($event->getMedia('gallery')->isNotEmpty())
                    <div class="mt-10">
                        <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Gallery</h2>
                        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            @foreach ($event->getMedia('gallery') as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block h-32 overflow-hidden rounded-lg">
                                    <x-ui.lazy-image :media="$media" :alt="$event->title" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-6">
                {{-- Registration --}}
                @if ($event->requires_registration)
                    <x-ui.card id="register">
                        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Register for This Event</h2>

                        @if (session('registration_status'))
                            <div class="mt-3 rounded-md border border-success-600/30 bg-green-50 px-4 py-3 text-sm text-success-600 dark:bg-night-surface-alt">
                                {{ session('registration_status') }}
                            </div>
                        @elseif ($isCancelled)
                            <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">Registrations are closed because this event was cancelled.</p>
                        @elseif ($event->isPast())
                            <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">This event has already taken place.</p>
                        @elseif ($event->isFull())
                            <p class="mt-3 rounded-md border border-warning-600/30 bg-amber-50 px-4 py-3 text-sm font-medium text-warning-600 dark:bg-night-surface-alt">
                                This event is fully booked.
                            </p>
                        @else
                            @if ($event->spotsLeft() !== null)
                                <p class="mt-2 text-xs font-semibold text-success-600">{{ $event->spotsLeft() }} of {{ $event->max_participants }} spots left</p>
                            @endif

                            @error('registration')
                                <div class="mt-3 rounded-md border border-error-600/30 bg-red-50 px-4 py-3 text-sm text-error-600 dark:bg-night-surface-alt">{{ $message }}</div>
                            @enderror

                            <form method="POST" action="{{ route('events.register', $event) }}" class="mt-4 space-y-4">
                                @csrf
                                <x-ui.input label="Full Name" name="name" value="{{ old('name') }}" required :error="$errors->first('name')" />
                                <x-ui.input label="Email" name="email" type="email" value="{{ old('email') }}" required :error="$errors->first('email')" />
                                <x-ui.input label="Phone" name="phone" type="tel" value="{{ old('phone') }}" required :error="$errors->first('phone')" />
                                <x-ui.input label="City" name="city" value="{{ old('city') }}" :error="$errors->first('city')" />

                                <div>
                                    <label for="message" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Message <span class="font-normal text-text-400">(optional)</span></label>
                                    <textarea id="message" name="message" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('message') }}</textarea>
                                    @error('message')
                                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                                    @enderror
                                </div>

                                <x-ui.button type="submit" class="w-full">Register Now</x-ui.button>
                            </form>
                        @endif
                    </x-ui.card>
                @endif

                {{-- Share --}}
                <x-ui.card>
                    <p class="text-sm font-semibold text-text-900 dark:text-night-text">Share This Event</p>
                    <div class="mt-3 flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on Facebook">
                            <x-ui.icon name="facebook" class="h-4 w-4" />
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($event->title) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on X">
                            <x-ui.icon name="x-twitter" class="h-4 w-4" />
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($event->title.' — '.$shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on WhatsApp">
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

                <x-ui.cta heading="Support This Event" variant="dark">
                    <x-ui.button href="{{ url('/donate') }}" variant="accent" size="sm">Donate Now</x-ui.button>
                </x-ui.cta>
                <x-ui.cta heading="Volunteer With Us" variant="muted">
                    <x-ui.button href="{{ url('/volunteer') }}" variant="secondary" size="sm">Get Involved</x-ui.button>
                </x-ui.cta>
            </aside>
        </div>
    </x-ui.section>

    @if ($related->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Related Events</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('events.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ $item->dateRange() }}{{ $item->city ? ' · '.$item->city : '' }}</p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif
@endsection
