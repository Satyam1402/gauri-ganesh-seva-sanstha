@extends('layouts.app')

@php
    $seo = $campaign->seo;
    $shareUrl = route('donations.campaigns.show', $campaign);
    $metaDescription = $seo?->meta_description ?? $campaign->short_description;
@endphp

@section('title', $seo?->meta_title ?? $campaign->name.' — '.config('app.name'))
@section('meta_description', $metaDescription)
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? $shareUrl)
@section('og_title', $seo?->og_title ?? $campaign->name)
@section('og_description', $seo?->og_description ?? $metaDescription)
@if ($seo?->ogImage ?? $campaign->getFirstMedia('featured_image'))
    @section('og_image', ($seo?->ogImage ?? $campaign->getFirstMedia('featured_image'))->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode(array_filter([
            '@@context' => 'https://schema.org',
            '@type' => 'DonateAction',
            'name' => $campaign->name,
            'description' => $metaDescription,
            'image' => $campaign->getFirstMedia('featured_image')?->getUrl(),
            'recipient' => [
                '@type' => 'NGO',
                'name' => config('app.name'),
            ],
            'target' => [
                '@type' => 'EntryPoint',
                'urlTemplate' => route('donations.donate', $campaign),
            ],
        ]), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[
            ['label' => 'Home', 'url' => route('home')],
            ['label' => 'Campaigns', 'url' => route('donations.campaigns.index')],
            ['label' => $campaign->name],
        ]" class="mb-6" />

        <div class="h-72 w-full overflow-hidden rounded-xl sm:h-96">
            <x-ui.lazy-image :media="$campaign->getFirstMedia('featured_image')" :alt="$campaign->name" conversion="webp" />
        </div>

        <div class="mt-6">
            @if ($campaign->is_featured)
                <x-ui.badge variant="accent">Featured Campaign</x-ui.badge>
            @endif
            @if ($campaign->status->value === 'completed')
                <x-ui.badge variant="success">Goal Reached</x-ui.badge>
            @endif

            <h1 class="mt-3 font-display text-3xl font-semibold text-text-900 dark:text-night-text">{{ $campaign->name }}</h1>

            @if ($campaign->end_date)
                <p class="mt-3 flex items-center gap-1.5 text-sm text-text-600 dark:text-night-text-muted">
                    <x-ui.icon name="calendar" class="h-5 w-5 text-primary-700" />
                    Runs until {{ $campaign->end_date->format('d M Y') }}
                </p>
            @endif
        </div>
    </x-ui.section>

    <x-ui.section background="base" spacing="sm">
        <div class="grid grid-cols-1 gap-10 lg:grid-cols-3">
            <div class="prose max-w-none lg:col-span-2 dark:prose-invert">
                {!! nl2br(e($campaign->full_description)) !!}
            </div>

            <aside class="space-y-6">
                <x-ui.card>
                    <p class="font-display text-2xl font-semibold text-primary-700 dark:text-night-text">{{ format_inr((float) $campaign->raised_amount) }}</p>
                    @if ($campaign->goal_amount)
                        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">raised of {{ format_inr((float) $campaign->goal_amount) }} goal</p>
                        <div class="mt-3 h-2.5 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                            <div class="h-full rounded-full bg-primary-700" style="width: {{ $campaign->progressPercent() }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-text-400 dark:text-night-text-muted">{{ $campaign->progressPercent() }}% of goal reached</p>
                    @else
                        <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">raised so far for this cause</p>
                    @endif

                    @if ($campaign->isAcceptingDonations())
                        <x-ui.button href="{{ route('donations.donate', $campaign) }}" variant="accent" class="mt-5 w-full justify-center">Donate Now</x-ui.button>
                    @else
                        <p class="mt-5 rounded-md bg-surface-muted px-3 py-2 text-center text-sm text-text-600 dark:bg-night-surface-alt dark:text-night-text-muted">
                            This campaign is not accepting donations right now.
                        </p>
                        <x-ui.button href="{{ route('donations.donate') }}" variant="secondary" class="mt-3 w-full justify-center">Make a General Donation</x-ui.button>
                    @endif
                </x-ui.card>

                @if ($recentDonations->isNotEmpty())
                    <x-ui.card>
                        <p class="text-sm font-semibold text-text-900 dark:text-night-text">Recent Supporters</p>
                        <div class="mt-3 divide-y divide-border-subtle dark:divide-night-border">
                            @foreach ($recentDonations as $donation)
                                <div class="flex items-center justify-between gap-3 py-2 text-sm">
                                    <span class="text-text-600 dark:text-night-text-muted">{{ $donation->publicDonorName() }}</span>
                                    <span class="font-medium text-text-900 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</span>
                                </div>
                            @endforeach
                        </div>
                    </x-ui.card>
                @endif

                <x-ui.card>
                    <p class="text-sm font-semibold text-text-900 dark:text-night-text">Share This Campaign</p>
                    <div class="mt-3 flex gap-3">
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode($shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on Facebook">
                            <x-ui.icon name="facebook" class="h-4 w-4" />
                        </a>
                        <a href="https://twitter.com/intent/tweet?url={{ urlencode($shareUrl) }}&text={{ urlencode($campaign->name) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on X">
                            <x-ui.icon name="x-twitter" class="h-4 w-4" />
                        </a>
                        <a href="https://wa.me/?text={{ urlencode($campaign->name.' — '.$shareUrl) }}" target="_blank" rel="noopener" class="flex h-9 w-9 items-center justify-center rounded-full bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted" aria-label="Share on WhatsApp">
                            <x-ui.icon name="whatsapp" class="h-4 w-4" />
                        </a>
                    </div>
                </x-ui.card>
            </aside>
        </div>
    </x-ui.section>

    @if ($others->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Other Ways to Help</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2">
                @foreach ($others as $item)
                    <a href="{{ route('donations.campaigns.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->name" conversion="webp" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->name }}</p>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ Str::limit($item->short_description, 90) }}</p>
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif
@endsection
