@extends('layouts.app')

@php $pageUrl = route('testimonials.index'); @endphp

@section('content')
    {{-- Hero --}}
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="$seo->breadcrumbItems()" class="mb-6" />

        <div class="mx-auto max-w-3xl text-center">
            <p class="text-sm font-semibold uppercase tracking-wide text-accent-500">In Their Own Words</p>
            <h1 class="mt-2 font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">Voices From Our Community</h1>
            <p class="mt-4 text-base text-text-600 dark:text-night-text-muted">
                Every story here is shared with permission by the people who received support, the donors who made it possible,
                and the volunteers and partners who stood alongside us.
            </p>
        </div>
    </x-ui.section>

    {{-- Featured strip (unfiltered first page only) --}}
    @if ($featured->isNotEmpty())
        <x-ui.section background="muted" spacing="sm">
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">Featured Stories</h2>
            <div class="mt-6 grid grid-cols-1 gap-6 md:grid-cols-3">
                @foreach ($featured as $testimonial)
                    <x-testimonials.card :testimonial="$testimonial" clamp />
                @endforeach
            </div>
        </x-ui.section>
    @endif

    <x-ui.section background="base">
        {{-- Type filter tabs --}}
        <nav aria-label="Filter testimonials by type" class="mb-8 flex flex-wrap gap-2">
            <a
                href="{{ $pageUrl }}"
                class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentType === null ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                @if ($currentType === null) aria-current="page" @endif
            >
                All
            </a>
            @foreach (App\Enums\TestimonialType::cases() as $type)
                @continue(empty($typeCounts[$type->value]))
                <a
                    href="{{ route('testimonials.index', ['type' => $type->value]) }}"
                    class="rounded-full px-4 py-1.5 text-sm font-medium focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $currentType === $type ? 'bg-primary-700 text-white' : 'bg-surface-muted text-text-600 hover:bg-primary-100 dark:bg-night-surface-alt dark:text-night-text-muted' }}"
                    @if ($currentType === $type) aria-current="page" @endif
                >
                    {{ $type->pluralLabel() }} <span class="opacity-75">({{ $typeCounts[$type->value] }})</span>
                </a>
            @endforeach
        </nav>

        @if ($currentType)
            <h2 class="mb-6 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $currentType->pluralLabel() }}</h2>
        @else
            <h2 class="sr-only">All testimonials</h2>
        @endif

        @if ($testimonials->isEmpty())
            <x-ui.empty-state
                heading="No testimonials yet"
                message="{{ $currentType ? 'No '.Str::lower($currentType->pluralLabel()).' have been shared yet — try another category.' : 'Stories from our community will appear here soon.' }}"
            />
        @else
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 lg:grid-cols-3">
                @foreach ($testimonials as $testimonial)
                    <x-testimonials.card :testimonial="$testimonial" />
                @endforeach
            </div>

            <div class="mt-10">
                {{ $testimonials->links() }}
            </div>
        @endif
    </x-ui.section>

    <x-ui.section background="white">
        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2">
            <x-ui.cta heading="Have a Story to Share?" subheading="If our work has touched your life, we would be honoured to hear from you — with your permission, it may inspire others." variant="muted">
                <x-ui.button href="{{ route('contact') }}" variant="secondary">Get in Touch</x-ui.button>
            </x-ui.cta>
            <x-ui.cta heading="Be Part of the Next Story" subheading="Your donation funds the camps, drives and classes behind every testimonial on this page." variant="dark">
                <x-ui.button href="{{ url('/donate') }}" variant="accent">Donate Now</x-ui.button>
            </x-ui.cta>
        </div>
    </x-ui.section>
@endsection
