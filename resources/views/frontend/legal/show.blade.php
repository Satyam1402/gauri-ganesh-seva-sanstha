@extends('layouts.app')

@section('content')
    <x-ui.section background="white" spacing="sm">
        <x-ui.breadcrumbs :items="$seo->breadcrumbItems()" class="mb-6" />
        <h1 class="font-display text-3xl font-semibold text-text-900 sm:text-4xl dark:text-night-text">{{ $title }}</h1>
        @if ($updatedAt)
            <p class="mt-2 text-sm text-text-400 dark:text-night-text-muted">Last updated {{ $updatedAt->format(setting('general.date_format', 'd M Y')) }}</p>
        @endif
    </x-ui.section>

    <x-ui.section background="base">
        <article class="prose max-w-3xl text-text-600 dark:text-night-text-muted">
            {!! $html !!}
        </article>
    </x-ui.section>
@endsection
