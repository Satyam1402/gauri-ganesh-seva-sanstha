{{-- Shared album form fields. $album is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $seo = $album?->seo;
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Album Details</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Title" name="title" value="{{ old('title', $album->title ?? '') }}" required :error="$errors->first('title')" />
        <x-ui.input label="Slug" name="slug" value="{{ old('slug', $album->slug ?? '') }}" helper="Leave blank to auto-generate from the title." :error="$errors->first('slug')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Category"
                name="gallery_category_id"
                :options="['' => 'Uncategorised'] + $categories->pluck('name', 'id')->all()"
                :selected="old('gallery_category_id', $album->gallery_category_id ?? '')"
                :error="$errors->first('gallery_category_id')"
            />
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $album->status?->value ?? 'draft')"
                :error="$errors->first('status')"
            />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="Event Date" name="event_date" type="date" value="{{ old('event_date', $album?->event_date?->toDateString()) }}" :error="$errors->first('event_date')" />
            <x-ui.input label="Location" name="location" value="{{ old('location', $album->location ?? '') }}" :error="$errors->first('location')" />
            <x-ui.input label="Display Order" name="order_column" type="number" min="0" value="{{ old('order_column', $album->order_column ?? 0) }}" :error="$errors->first('order_column')" />
        </div>

        <div>
            <label for="description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Description</label>
            <textarea id="description" name="description" rows="4" class="{{ $textareaClasses }}">{{ old('description', $album->description ?? '') }}</textarea>
            @error('description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $album->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this album on the gallery page
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Cover Image</h3>
    <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Optional — the first photo in the album is used when no cover is set.</p>

    <div class="mt-4 space-y-5">
        @if ($album?->getFirstMedia('cover_image'))
            <div class="flex items-center gap-4">
                <div class="h-20 w-32 overflow-hidden rounded-md">
                    <x-ui.lazy-image :media="$album->getFirstMedia('cover_image')" :alt="$album->title" conversion="thumb" />
                </div>
                <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="remove_cover_image" value="1" class="rounded border-border-subtle text-primary-700">
                    Remove current cover
                </label>
            </div>
        @endif

        <div>
            <input type="file" name="cover_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('cover_image')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">SEO</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Meta Title" name="meta_title" value="{{ old('meta_title', $seo->meta_title ?? '') }}" helper="Recommended: under 70 characters." :error="$errors->first('meta_title')" />

        <div>
            <label for="meta_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Meta Description</label>
            <textarea id="meta_description" name="meta_description" rows="2" class="{{ $textareaClasses }}">{{ old('meta_description', $seo->meta_description ?? '') }}</textarea>
            @error('meta_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.input label="Meta Keywords" name="meta_keywords" value="{{ old('meta_keywords', $seo->meta_keywords ?? '') }}" helper="Comma-separated." :error="$errors->first('meta_keywords')" />
        <x-ui.input label="Canonical URL" name="canonical_url" value="{{ old('canonical_url', $seo->canonical_url ?? '') }}" :error="$errors->first('canonical_url')" />

        <hr class="border-border-subtle dark:border-night-border">

        <x-ui.input label="OG Title" name="og_title" value="{{ old('og_title', $seo->og_title ?? '') }}" :error="$errors->first('og_title')" />

        <div>
            <label for="og_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">OG Description</label>
            <textarea id="og_description" name="og_description" rows="2" class="{{ $textareaClasses }}">{{ old('og_description', $seo->og_description ?? '') }}</textarea>
            @error('og_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">OG Image</p>
            @if ($album?->getFirstMedia('og_image'))
                <label class="mb-2 flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="remove_og_image" value="1" class="rounded border-border-subtle text-primary-700">
                    Remove current OG image
                </label>
            @endif
            <input type="file" name="og_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('og_image')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.select
            label="Twitter Card Type"
            name="twitter_card"
            :options="['summary' => 'Summary', 'summary_large_image' => 'Summary with Large Image']"
            :selected="old('twitter_card', $seo->twitter_card ?? 'summary_large_image')"
        />

        <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', $seo->schema_type ?? 'ImageGallery') }}" helper="e.g. ImageGallery, MediaGallery." :error="$errors->first('schema_type')" />
    </div>
</x-ui.card>
