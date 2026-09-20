@extends('layouts.admin')

@section('title', 'SEO — '.$page->title)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'SEO', 'url' => route('admin.seo.index')],
        ['label' => $page->title],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ $page->title }}</h1>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">
                <a href="{{ $publicUrl }}" target="_blank" rel="noopener" class="text-primary-700 hover:underline dark:text-night-text">{{ $publicUrl }} ↗</a>
            </p>
        </div>
        <x-ui.button href="{{ route('admin.seo.index') }}" variant="ghost" size="sm">← All pages</x-ui.button>
    </div>

    <form method="POST" action="{{ route('admin.pages.seo.update', $page) }}" enctype="multipart/form-data" class="max-w-4xl space-y-6">
        @csrf
        @method('PUT')

        @include('admin.partials.seo-fields', [
            'seo' => $page->seo,
            'model' => $page,
            'previewTitle' => $page->title,
            'previewDescription' => setting('general.site_description'),
            'previewUrl' => $publicUrl,
            'schemaHint' => $schemaHint,
        ])

        <div class="flex gap-3">
            <x-ui.button type="submit">Save SEO</x-ui.button>
            <x-ui.button href="{{ route('admin.seo.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
