@extends('layouts.app')

@php $cover = $partner->getFirstMedia('cover_image'); @endphp

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="$seo->breadcrumbItems()" class="mb-6" />

        @if ($cover)
            <div class="mb-8 aspect-[3/1] w-full overflow-hidden rounded-2xl bg-surface-muted dark:bg-night-surface-alt">
                <x-ui.lazy-image :media="$cover" :alt="$partner->name.' cover image'" conversion="webp" />
            </div>
        @endif

        <div class="flex flex-col gap-6 sm:flex-row sm:items-start">
            <div class="w-full max-w-xs shrink-0">
                <x-partners.logo :partner="$partner" class="bg-surface-muted dark:bg-night-surface-alt" />
            </div>

            <div class="min-w-0 flex-1">
                <div class="flex flex-wrap items-center gap-2">
                    @if ($partner->type)
                        <x-ui.badge variant="accent">{{ $partner->type->name }}</x-ui.badge>
                    @endif
                    @if ($partner->partnershipPeriod())
                        <span class="text-sm text-text-400 dark:text-night-text-muted">{{ $partner->partnershipPeriod() }}</span>
                    @endif
                </div>

                <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">{{ $partner->name }}</h1>

                @if ($partner->short_description)
                    <p class="mt-3 text-lg text-text-600 dark:text-night-text-muted">{{ $partner->short_description }}</p>
                @endif

                <dl class="mt-5 flex flex-wrap gap-x-8 gap-y-3 text-sm">
                    @if ($partner->locationLine())
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Based in</dt>
                            <dd class="mt-0.5 text-text-900 dark:text-night-text">{{ $partner->locationLine() }}</dd>
                        </div>
                    @endif
                    @if ($partner->website_url)
                        <div>
                            <dt class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">Website</dt>
                            <dd class="mt-0.5">
                                <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-primary-700 hover:underline dark:text-night-text">
                                    {{ $partner->websiteHost() }}<span class="sr-only"> (opens in a new tab)</span>
                                </a>
                            </dd>
                        </div>
                    @endif
                </dl>
            </div>
        </div>
    </x-ui.section>

    @if ($partner->full_description)
        <x-ui.section background="base" spacing="sm">
            <div class="prose max-w-3xl text-text-600 dark:text-night-text-muted">
                {!! $partner->fullDescriptionHtml() !!}
            </div>
        </x-ui.section>
    @endif

    @if ($related->isNotEmpty())
        <x-ui.section background="muted">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">More {{ $partner->type?->name ?? 'Partner' }}s</h2>
            <ul class="mt-6 grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-4" role="list">
                @foreach ($related as $item)
                    <li><x-partners.card :partner="$item" :show-description="false" /></li>
                @endforeach
            </ul>
        </x-ui.section>
    @endif

    <x-ui.section background="white">
        <x-ui.cta heading="Partner With Us" subheading="Join the organisations helping us restore dignity in the communities we serve." variant="muted">
            <x-ui.button href="{{ route('contact') }}" variant="secondary">Get in Touch</x-ui.button>
        </x-ui.cta>
    </x-ui.section>
@endsection
