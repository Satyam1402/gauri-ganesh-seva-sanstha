@extends('layouts.app')

@php
    $pageTitle = $heading ?? 'Blog & News';
    $pageSubheading = $subheading ?? 'Stories from the ground — news, updates, success stories, and announcements from '.config('app.name').'.';
    $isFiltered = ! empty($filters['q']) || ! empty($filters['category']) || ! empty($filters['tag']);
@endphp

@section('title', $pageTitle.' — '.config('app.name'))
@section('meta_description', Str::limit($pageSubheading, 160))
@section('canonical_url', url()->current())
@section('og_title', $pageTitle.' — '.config('app.name'))
@section('og_description', Str::limit($pageSubheading, 160))

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $pageTitle.' — '.config('app.name'),
            'url' => url()->current(),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ...($heading ? [['label' => 'Blog', 'url' => route('blog.index')], ['label' => $heading]] : [['label' => 'Blog']]),
        ]" class="mb-4" />

        <x-ui.section-heading :heading="$pageTitle" :subheading="$pageSubheading" />
    </x-ui.section>

    {{-- Featured posts — only on the unfiltered main listing --}}
    @if (! $isFiltered && $featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Stories</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($featured as $item)
                    <a href="{{ route('blog.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                @if ($item->category)
                                    <x-ui.badge variant="accent">{{ $item->category->name }}</x-ui.badge>
                                @endif
                                <p class="mt-2 font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">
                                    {{ $item->published_at->format('d M Y') }} · {{ $item->reading_minutes }} min read
                                </p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            {{-- Posts grid --}}
            <div class="lg:col-span-2">
                @if (! empty($filters['q']))
                    <p class="mb-6 text-sm text-text-600 dark:text-night-text-muted">
                        Showing results for “<span class="font-semibold text-text-900 dark:text-night-text">{{ $filters['q'] }}</span>”
                        — <a href="{{ route('blog.index') }}" class="text-primary-700 hover:underline dark:text-night-text">clear search</a>
                    </p>
                @endif

                @if ($posts->isEmpty())
                    <x-ui.empty-state
                        heading="No articles found"
                        message="Try a different search term or browse by category — new stories are published regularly."
                    />
                @else
                    <div class="grid grid-cols-1 gap-8 sm:grid-cols-2">
                        @foreach ($posts as $post)
                            <a href="{{ route('blog.show', $post) }}" class="block">
                                <x-ui.card hoverable class="h-full overflow-hidden p-0">
                                    <div class="relative h-48 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                        <x-ui.lazy-image :media="$post->getFirstMedia('featured_image')" :alt="$post->title" conversion="thumb" />
                                        @if ($post->category)
                                            <span class="absolute left-3 top-3 rounded-md bg-white/95 px-2.5 py-1 text-xs font-semibold text-primary-700 shadow dark:bg-night-surface dark:text-night-text">
                                                {{ $post->category->name }}
                                            </span>
                                        @endif
                                    </div>
                                    <div class="p-5">
                                        <p class="text-xs text-text-400 dark:text-night-text-muted">
                                            {{ $post->published_at->format('d M Y') }} · {{ $post->author?->name ?? config('app.name') }} · {{ $post->reading_minutes }} min read
                                        </p>
                                        <p class="mt-2 font-display text-lg font-semibold text-text-900 dark:text-night-text">{{ $post->title }}</p>
                                        <p class="mt-2 text-sm text-text-600 dark:text-night-text-muted">{{ Str::limit($post->excerpt, 120) }}</p>
                                        @if ($post->tags->isNotEmpty())
                                            <div class="mt-3 flex flex-wrap gap-1.5">
                                                @foreach ($post->tags->take(3) as $tag)
                                                    <span class="rounded-full bg-surface-muted px-2.5 py-0.5 text-xs text-text-600 dark:bg-night-surface-alt dark:text-night-text-muted">#{{ $tag->name }}</span>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>
                                </x-ui.card>
                            </a>
                        @endforeach
                    </div>

                    <div class="mt-10">
                        {{ $posts->links() }}
                    </div>
                @endif
            </div>

            {{-- Sidebar --}}
            <aside class="space-y-6">
                <x-ui.card>
                    <p class="text-sm font-semibold text-text-900 dark:text-night-text">Search Articles</p>
                    <form method="GET" action="{{ route('blog.index') }}" class="mt-3 flex gap-2">
                        <div class="flex-1">
                            <x-ui.input name="q" placeholder="Search..." value="{{ $filters['q'] ?? '' }}" />
                        </div>
                        <x-ui.button type="submit" variant="secondary">Go</x-ui.button>
                    </form>
                </x-ui.card>

                @if ($categories->isNotEmpty())
                    <x-ui.card>
                        <p class="text-sm font-semibold text-text-900 dark:text-night-text">Categories</p>
                        <ul class="mt-3 space-y-1">
                            @foreach ($categories as $category)
                                <li>
                                    <a
                                        href="{{ route('blog.category', $category) }}"
                                        class="flex items-center justify-between rounded-md px-2 py-1.5 text-sm {{ ($filters['category'] ?? null) === $category->slug ? 'bg-primary-100 font-medium text-primary-700 dark:bg-night-surface-alt dark:text-night-text' : 'text-text-600 hover:bg-surface-muted hover:text-primary-700 dark:text-night-text-muted dark:hover:bg-night-surface-alt' }}"
                                    >
                                        <span>{{ $category->name }}</span>
                                        <span class="text-xs text-text-400 dark:text-night-text-muted">{{ $category->posts_count }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif

                @if ($popular->isNotEmpty())
                    <x-ui.card>
                        <p class="text-sm font-semibold text-text-900 dark:text-night-text">Popular Posts</p>
                        <ul class="mt-3 space-y-4">
                            @foreach ($popular as $item)
                                <li>
                                    <a href="{{ route('blog.show', $item) }}" class="flex items-center gap-3">
                                        <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md bg-surface-muted dark:bg-night-surface-alt">
                                            <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" conversion="thumb" />
                                        </div>
                                        <div>
                                            <p class="text-sm font-medium leading-snug text-text-900 hover:text-primary-700 dark:text-night-text">{{ Str::limit($item->title, 55) }}</p>
                                            <p class="mt-0.5 text-xs text-text-400 dark:text-night-text-muted">{{ $item->published_at->format('d M Y') }}</p>
                                        </div>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </x-ui.card>
                @endif

                <x-ui.cta heading="Support Our Work" variant="dark">
                    <x-ui.button href="{{ url('/donate') }}" variant="accent" size="sm">Donate Now</x-ui.button>
                </x-ui.cta>
            </aside>
        </div>
    </x-ui.section>
@endsection
