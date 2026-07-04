@extends('layouts.app')

@php $seo = $page?->seo; @endphp

@section('title', $seo?->meta_title ?? 'About Us — '.config('app.name'))
@section('meta_description', $seo?->meta_description ?? 'Learn about the mission, story, and people behind '.config('app.name').'.')
@if ($seo?->meta_keywords)
    @section('meta_keywords', $seo->meta_keywords)
@endif
@section('canonical_url', $seo?->canonical_url ?? url('/about'))
@section('og_title', $seo?->og_title ?? 'About '.config('app.name'))
@section('og_description', $seo?->og_description ?? 'Learn about our mission, our story, and the people behind our work.')
@if ($seo?->ogImage)
    @section('og_image', $seo->ogImage->getUrl())
@endif
@section('twitter_card', $seo?->twitter_card ?? 'summary_large_image')

@push('structured_data')
    <script type="application/ld+json">
        {!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => $seo?->schema_type ?? 'AboutPage',
            'name' => 'About Us — '.config('app.name'),
            'url' => url('/about'),
            'description' => $seo?->meta_description,
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) !!}
    </script>
@endpush

@section('content')
    @forelse ($sections as $section)
        @include('frontend.about.sections.'.str_replace('_', '-', $section->key->value), ['section' => $section, 'orgProfile' => $orgProfile])
    @empty
        <x-ui.container class="py-24">
            <x-ui.empty-state
                heading="About page content is being set up"
                message="Sections will appear here once enabled from the admin panel."
            />
        </x-ui.container>
    @endforelse
@endsection
