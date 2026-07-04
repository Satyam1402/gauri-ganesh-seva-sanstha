@extends('layouts.admin')

@section('title', 'SEO — '.$page->title)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Homepage', 'url' => route('admin.home-sections.index')],
        ['label' => 'SEO'],
    ]" />
@endsection

@php
    $seo = $page->seo;
    $ogImage = $page->getFirstMedia('og_image');
@endphp

@section('content')
    <x-ui.card class="max-w-3xl">
        <form method="POST" action="{{ route('admin.pages.seo.update', $page) }}" enctype="multipart/form-data" class="space-y-5">
            @csrf
            @method('PUT')

            <x-ui.input label="Meta Title" name="meta_title" value="{{ old('meta_title', $seo?->meta_title) }}" helper="Recommended: under 70 characters." :error="$errors->first('meta_title')" />

            <div>
                <label for="meta_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Meta Description</label>
                <textarea id="meta_description" name="meta_description" rows="3" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('meta_description', $seo?->meta_description) }}</textarea>
                <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Recommended: under 160 characters.</p>
                @error('meta_description')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.input label="Meta Keywords" name="meta_keywords" value="{{ old('meta_keywords', $seo?->meta_keywords) }}" helper="Comma-separated." :error="$errors->first('meta_keywords')" />
            <x-ui.input label="Canonical URL" name="canonical_url" value="{{ old('canonical_url', $seo?->canonical_url) }}" :error="$errors->first('canonical_url')" />

            <hr class="border-border-subtle dark:border-night-border">

            <x-ui.input label="OG Title" name="og_title" value="{{ old('og_title', $seo?->og_title) }}" :error="$errors->first('og_title')" />

            <div>
                <label for="og_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">OG Description</label>
                <textarea id="og_description" name="og_description" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('og_description', $seo?->og_description) }}</textarea>
                @error('og_description')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">OG Image</p>
                @if ($ogImage)
                    <img src="{{ $ogImage->getUrl() }}" alt="" class="mb-2 h-32 w-full max-w-sm rounded-md object-cover">
                    <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                        <input type="checkbox" name="remove_og_image" value="1" class="rounded border-border-subtle text-error-600">
                        Remove current OG image
                    </label>
                @endif
                <input type="file" name="og_image" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-text-600 dark:text-night-text-muted">
                @error('og_image')
                    <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                @enderror
            </div>

            <x-ui.select
                label="Twitter Card Type"
                name="twitter_card"
                :options="['summary' => 'Summary', 'summary_large_image' => 'Summary with Large Image']"
                :selected="old('twitter_card', $seo?->twitter_card ?? 'summary_large_image')"
            />

            <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', $seo?->schema_type) }}" helper="e.g. NGO, Organization, WebPage." :error="$errors->first('schema_type')" />

            <x-ui.button type="submit">Save SEO Settings</x-ui.button>
        </form>
    </x-ui.card>
@endsection
