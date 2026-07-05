@extends('layouts.app')

@section('title', 'Donation Campaigns — '.config('app.name'))
@section('meta_description', 'Support food distribution, education, medical assistance, and other seva programs run by '.config('app.name').'. Every contribution counts.')
@section('canonical_url', url('/campaigns'))
@section('og_title', 'Donation Campaigns — '.config('app.name'))
@section('og_description', 'Choose a cause close to your heart and make a difference today.')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => 'Donation Campaigns — '.config('app.name'),
            'url' => url('/campaigns'),
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="[['label' => 'Home', 'url' => route('home')], ['label' => 'Campaigns']]" class="mb-4" />

        <x-ui.section-heading heading="Donation Campaigns" subheading="Choose a cause close to your heart — every rupee goes directly to the community." />

        <div class="mt-6">
            <x-ui.button href="{{ route('donations.donate') }}" variant="accent">Make a General Donation</x-ui.button>
        </div>
    </x-ui.section>

    @if (empty($filters['q']) && $featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Campaigns</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-3">
                @foreach ($featured as $item)
                    <a href="{{ route('donations.campaigns.show', $item) }}" class="block">
                        <x-ui.card hoverable class="h-full overflow-hidden p-0">
                            <div class="h-40 w-full overflow-hidden">
                                <x-ui.lazy-image :media="$item->getFirstMedia('featured_image')" :alt="$item->name" conversion="webp" />
                            </div>
                            <div class="p-4">
                                <p class="font-semibold text-text-900 dark:text-night-text">{{ $item->name }}</p>
                                @if ($item->goal_amount)
                                    <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                                        <div class="h-full rounded-full bg-accent-500" style="width: {{ $item->progressPercent() }}%"></div>
                                    </div>
                                    <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">{{ format_inr((float) $item->raised_amount) }} of {{ format_inr((float) $item->goal_amount) }}</p>
                                @endif
                            </div>
                        </x-ui.card>
                    </a>
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        <form method="GET" action="{{ route('donations.campaigns.index') }}" class="mb-8 flex flex-wrap items-center gap-3">
            <div class="w-full max-w-xs">
                <x-ui.input name="q" placeholder="Search campaigns..." value="{{ $filters['q'] ?? '' }}" />
            </div>
            <x-ui.button type="submit" variant="secondary">Search</x-ui.button>
        </form>

        @if ($campaigns->isEmpty())
            <x-ui.empty-state heading="No active campaigns" message="Please check back soon, or make a general donation to support our work." />
        @else
            <div class="grid grid-cols-1 gap-8 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($campaigns as $campaign)
                    <x-ui.card hoverable class="flex h-full flex-col overflow-hidden p-0">
                        <a href="{{ route('donations.campaigns.show', $campaign) }}" class="block h-48 w-full overflow-hidden">
                            <x-ui.lazy-image :media="$campaign->getFirstMedia('featured_image')" :alt="$campaign->name" conversion="webp" />
                        </a>
                        <div class="flex flex-1 flex-col p-5">
                            @if ($campaign->is_featured)
                                <x-ui.badge variant="accent">Featured</x-ui.badge>
                            @endif
                            <a href="{{ route('donations.campaigns.show', $campaign) }}">
                                <p class="mt-3 font-display text-lg font-semibold text-text-900 hover:text-primary-700 dark:text-night-text">{{ $campaign->name }}</p>
                            </a>
                            <p class="mt-2 flex-1 text-sm text-text-600 dark:text-night-text-muted">{{ Str::limit($campaign->short_description, 110) }}</p>

                            <div class="mt-4">
                                @if ($campaign->goal_amount)
                                    <div class="h-2 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                                        <div class="h-full rounded-full bg-primary-700" style="width: {{ $campaign->progressPercent() }}%"></div>
                                    </div>
                                    <div class="mt-2 flex justify-between text-xs text-text-600 dark:text-night-text-muted">
                                        <span class="font-semibold text-primary-700 dark:text-night-text">{{ format_inr((float) $campaign->raised_amount) }} raised</span>
                                        <span>Goal: {{ format_inr((float) $campaign->goal_amount) }}</span>
                                    </div>
                                @else
                                    <p class="text-xs text-text-600 dark:text-night-text-muted">
                                        <span class="font-semibold text-primary-700 dark:text-night-text">{{ format_inr((float) $campaign->raised_amount) }}</span> raised so far
                                    </p>
                                @endif
                            </div>

                            <div class="mt-4">
                                <x-ui.button href="{{ route('donations.donate', $campaign) }}" size="sm" class="w-full justify-center">Donate Now</x-ui.button>
                            </div>
                        </div>
                    </x-ui.card>
                @endforeach
            </div>

            <div class="mt-10">
                {{ $campaigns->links() }}
            </div>
        @endif
    </x-ui.section>
@endsection
