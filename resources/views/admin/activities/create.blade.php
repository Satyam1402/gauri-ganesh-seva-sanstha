@extends('layouts.admin')

@section('title', 'Add Activity')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Activities', 'url' => route('admin.activities.index')],
        ['label' => 'Add Activity'],
    ]" />
@endsection

@section('content')
    <form method="POST" action="{{ route('admin.activities.store') }}" enctype="multipart/form-data" class="space-y-6">
        @csrf

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Details</h3>

            <div class="mt-4 space-y-5">
                <x-ui.input label="Title" name="title" value="{{ old('title') }}" required :error="$errors->first('title')" />
                <x-ui.input label="Slug" name="slug" value="{{ old('slug') }}" helper="Leave blank to auto-generate from the title." :error="$errors->first('slug')" />

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                    <x-ui.select
                        label="Category"
                        name="activity_category_id"
                        :options="['' => 'Select a category'] + $categories->pluck('name', 'id')->all()"
                        :selected="old('activity_category_id')"
                        :error="$errors->first('activity_category_id')"
                    />
                    <x-ui.select
                        label="Status"
                        name="status"
                        :options="$statuses"
                        :selected="old('status', 'draft')"
                        :error="$errors->first('status')"
                    />
                </div>

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <x-ui.input label="Activity Date" name="activity_date" type="date" value="{{ old('activity_date') }}" required :error="$errors->first('activity_date')" />
                    <x-ui.input label="Location" name="location" value="{{ old('location') }}" :error="$errors->first('location')" />
                    <x-ui.input label="Organizer" name="organizer" value="{{ old('organizer') }}" :error="$errors->first('organizer')" />
                </div>

                <div>
                    <label for="short_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Short Description</label>
                    <textarea id="short_description" name="short_description" rows="2" maxlength="300" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('short_description') }}</textarea>
                    <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Shown on listing cards. Max 300 characters.</p>
                    @error('short_description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label for="full_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Full Description</label>
                    <textarea id="full_description" name="full_description" rows="8" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('full_description') }}</textarea>
                    @error('full_description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured')) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
                    Feature this activity
                </label>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Images</h3>

            <div class="mt-4 space-y-5">
                <div>
                    <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Featured Image</p>
                    <input type="file" name="featured_image" accept="image/png,image/jpeg,image/webp" required class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                    @error('featured_image')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Gallery Images</p>
                    <input type="file" name="gallery[]" accept="image/png,image/jpeg,image/webp" multiple class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                    @error('gallery')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">SEO</h3>

            <div class="mt-4 space-y-5">
                <x-ui.input label="Meta Title" name="meta_title" value="{{ old('meta_title') }}" helper="Recommended: under 70 characters." :error="$errors->first('meta_title')" />

                <div>
                    <label for="meta_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Meta Description</label>
                    <textarea id="meta_description" name="meta_description" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('meta_description') }}</textarea>
                    @error('meta_description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.input label="Meta Keywords" name="meta_keywords" value="{{ old('meta_keywords') }}" helper="Comma-separated." :error="$errors->first('meta_keywords')" />
                <x-ui.input label="Canonical URL" name="canonical_url" value="{{ old('canonical_url') }}" :error="$errors->first('canonical_url')" />

                <hr class="border-border-subtle dark:border-night-border">

                <x-ui.input label="OG Title" name="og_title" value="{{ old('og_title') }}" :error="$errors->first('og_title')" />

                <div>
                    <label for="og_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">OG Description</label>
                    <textarea id="og_description" name="og_description" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('og_description') }}</textarea>
                    @error('og_description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">OG Image</p>
                    <input type="file" name="og_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                    @error('og_image')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <x-ui.select
                    label="Twitter Card Type"
                    name="twitter_card"
                    :options="['summary' => 'Summary', 'summary_large_image' => 'Summary with Large Image']"
                    :selected="old('twitter_card', 'summary_large_image')"
                />

                <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', 'Event') }}" helper="e.g. Event, Article." :error="$errors->first('schema_type')" />
            </div>
        </x-ui.card>

        <div class="flex gap-3">
            <x-ui.button type="submit">Create Activity</x-ui.button>
            <x-ui.button href="{{ route('admin.activities.index') }}" variant="ghost">Cancel</x-ui.button>
        </div>
    </form>
@endsection
