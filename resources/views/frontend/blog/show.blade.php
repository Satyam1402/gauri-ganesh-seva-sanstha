@extends('layouts.app')

@php
    $seo = $post->seo;
    $shareUrl = route('blog.show', $post);
    $metaDescription = $seo?->meta_description ?? Str::limit($post->excerpt, 160);
@endphp

@section('title', $seo?->meta_title ?? $post->title.' — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $shareUrl)
@section('og_title', $seo?->og_title ?? $post->title)
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage ?? $post->getFirstMedia('featured_image'))
    @section('og_image', ($seo?->ogImage ?? $post->getFirstMedia('featured_image'))->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'Article',
            'headline' => $post->title,
            'description' => $metaDescription,
            'image' => $post->getFirstMedia('featured_image')?->getUrl(),
            'datePublished' => $post->published_at->toIso8601String(),
            'dateModified' => $post->updated_at->toIso8601String(),
            'mainEntityOfPage' => ['@type' => 'WebPage', '@id' => $shareUrl],
            'author' => [
                '@type' => 'Person',
                'name' => $post->author?->name ?? config('app.name'),
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'url' => url('/'),
            ],
            'articleSection' => $post->category?->name,
            'keywords' => $post->tags->pluck('name')->implode(', ') ?: null,
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Blog', 'url' => route('blog.index')],
            ...($post->category ? [['label' => $post->category->name, 'url' => route('blog.category', $post->category)]] : []),
            ['label' => $post->title],
        ]" class="mb-6" />

        <div class="h-72 w-full overflow-hidden rounded-xl sm:h-96">
            <x-ui.lazy-image :media="$post->getFirstMedia('featured_image')" :alt="$post->title" conversion="webp" />
        </div>

        <div class="mt-6">
            @if ($post->category)
                <a href="{{ route('blog.category', $post->category) }}">
                    <x-ui.badge variant="accent">{{ $post->category->name }}</x-ui.badge>
                </a>
            @endif

            <h1 class="mt-3 font-display text-3xl font-semibold text-text-900 dark:text-night-text">{{ $post->title }}</h1>

            <div class="mt-4 flex flex-wrap items-center gap-5 text-sm text-text-600 dark:text-night-text-muted">
                <span class="flex items-center gap-1.5"><x-ui.icon name="users" class="h-5 w-5 text-primary-700" /> {{ $post->author?->name ?? config('app.name') }}</span>
                <span class="flex items-center gap-1.5"><x-ui.icon name="calendar" class="h-5 w-5 text-primary-700" /> {{ $post->published_at->format('d M Y') }}</span>
                <span class="flex items-center gap-1.5"><x-ui.icon name="clock" class="h-5 w-5 text-primary-700" /> {{ $post->reading_minutes }} min read</span>
                <span>{{ number_format($post->views_count) }} {{ Str::plural('view', $post->views_count) }}</span>
            </div>
        </div>
    </x-ui.section>

    <x-ui.section background="base" spacing="sm">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="lg:col-span-2">
                <div class="prose max-w-none dark:prose-invert">
                    {!! Str::markdown($post->content, ['html_input' => 'strip', 'allow_unsafe_links' => false]) !!}
                </div>

                @if ($post->getMedia('gallery')->isNotEmpty())
                    <div class="mt-10">
                        <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Gallery</h2>
                        <div class="mt-5 grid grid-cols-2 gap-4 sm:grid-cols-4">
                            @foreach ($post->getMedia('gallery') as $media)
                                <a href="{{ $media->getUrl() }}" target="_blank" rel="noopener" class="block h-32 overflow-hidden rounded-lg">
                                    <x-ui.lazy-image :media="$media" :alt="$post->title" />
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif

                @if ($post->tags->isNotEmpty())
                    <div class="mt-10 flex flex-wrap items-center gap-2">
                        <span class="text-sm font-semibold text-text-900 dark:text-night-text">Tags:</span>
                        @foreach ($post->tags as $tag)
                            <a
                                href="{{ route('blog.tag', $tag) }}"
                                class="rounded-full bg-surface-muted px-3 py-1 text-sm text-text-600 hover:bg-primary-100 hover:text-primary-700 dark:bg-night-surface-alt dark:text-night-text-muted"
                            >
                                #{{ $tag->name }}
                            </a>
                        @endforeach
                    </div>
                @endif

                {{-- Comments --}}
                @if ($post->allow_comments)
                    <div class="mt-12" id="comments">
                        <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">
                            Comments ({{ $comments->count() }})
                        </h2>

                        @if ($comments->isEmpty())
                            <p class="mt-4 text-sm text-text-600 dark:text-night-text-muted">No comments yet — be the first to share your thoughts.</p>
                        @else
                            <ul class="mt-6 space-y-6">
                                @foreach ($comments as $comment)
                                    <li class="rounded-lg border border-border-subtle bg-surface-white p-5 dark:border-night-border dark:bg-night-surface">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-9 w-9 items-center justify-center rounded-full bg-primary-100 text-sm font-semibold uppercase text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                                                {{ Str::substr($comment->name, 0, 1) }}
                                            </span>
                                            <div>
                                                <p class="text-sm font-semibold text-text-900 dark:text-night-text">{{ $comment->name }}</p>
                                                <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $comment->created_at->format('d M Y, h:i A') }}</p>
                                            </div>
                                        </div>
                                        <p class="mt-3 text-sm text-text-600 dark:text-night-text-muted">{{ $comment->body }}</p>
                                    </li>
                                @endforeach
                            </ul>
                        @endif

                        <div class="mt-8">
                            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Leave a Comment</h3>
                            <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Your email is never published. Comments appear after moderation.</p>

                            @if (session('comment_status'))
                                <div class="mt-4 rounded-md border border-success-600/30 bg-green-50 px-4 py-3 text-sm text-success-600 dark:bg-night-surface-alt">
                                    {{ session('comment_status') }}
                                </div>
                            @else
                                <form method="POST" action="{{ route('blog.comments.store', $post) }}" class="mt-4 space-y-4">
                                    @csrf
                                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                        <x-ui.input label="Name" name="name" value="{{ old('name') }}" required :error="$errors->first('name')" />
                                        <x-ui.input label="Email" name="email" type="email" value="{{ old('email') }}" required :error="$errors->first('email')" />
                                    </div>

                                    <div>
                                        <label for="body" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Comment</label>
                                        <textarea id="body" name="body" rows="4" required maxlength="2000" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('body') }}</textarea>
                                        @error('body')
                                            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                                        @enderror
                                    </div>

                                    <x-ui.button type="submit">Submit Comment</x-ui.button>
                                </form>
                            @endif
                        </div>
                    </div>
                @endif
            </div>

            <aside class="space-y-6">
                {{-- Share --}}
                <x-ui.card>
                    <p class="text-sm font-semibold text-text-900 dark:text-night-text">Share This Article</p>
                    <div class="mt-3 flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on Facebook">
                            <x-ui.icon name="facebook" class="h-4 w-4" />
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($post->title) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on X">
                            <x-ui.icon name="x-twitter" class="h-4 w-4" />
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($post->title.' — '.$shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on WhatsApp">
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
                <x-ui.cta heading="Volunteer With Us" variant="muted">
                    <x-ui.button href="{{ url('/volunteer') }}" variant="secondary" size="sm">Get Involved</x-ui.button>
                </x-ui.cta>
            </aside>
        </div>
    </x-ui.section>

    @if ($related->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Related Articles</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($related as $item)
                    <a href="{{ route('blog.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->title" conversion="thumb" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->title }}</p>
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
@endsection
