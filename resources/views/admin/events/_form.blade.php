{{-- Shared event form fields. $event is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $seo = $event?->seo;
    $featuredImage = $event?->getFirstMedia('featured_image');
    $galleryImages = $event?->getMedia('gallery') ?? collect();
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Event Details</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Title" name="title" value="{{ old('title', $event->title ?? '') }}" required :error="$errors->first('title')" />
        <x-ui.input label="Slug" name="slug" value="{{ old('slug', $event->slug ?? '') }}" helper="Leave blank to auto-generate from the title." :error="$errors->first('slug')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Category"
                name="event_category_id"
                :options="['' => 'Uncategorised'] + $categories->pluck('name', 'id')->all()"
                :selected="old('event_category_id', $event->event_category_id ?? '')"
                :error="$errors->first('event_category_id')"
            />
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $event->status?->value ?? 'draft')"
                :error="$errors->first('status')"
            />
        </div>

        <div>
            <label for="short_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Short Description</label>
            <textarea id="short_description" name="short_description" rows="2" maxlength="300" required class="{{ $textareaClasses }}">{{ old('short_description', $event->short_description ?? '') }}</textarea>
            @error('short_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="full_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Full Description</label>
            <textarea id="full_description" name="full_description" rows="8" required class="{{ $textareaClasses }}">{{ old('full_description', $event->full_description ?? '') }}</textarea>
            @error('full_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $event->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this event on the events page
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Date &amp; Time</h3>

    <div class="mt-4 grid grid-cols-1 gap-5 sm:grid-cols-2">
        <x-ui.input label="Start Date" name="start_date" type="date" value="{{ old('start_date', $event?->start_date?->format('Y-m-d')) }}" required :error="$errors->first('start_date')" />
        <x-ui.input label="End Date" name="end_date" type="date" value="{{ old('end_date', $event?->end_date?->format('Y-m-d')) }}" helper="Leave blank for single-day events." :error="$errors->first('end_date')" />
        <x-ui.input label="Start Time" name="start_time" type="time" value="{{ old('start_time', $event?->start_time ? \Carbon\Carbon::parse($event->start_time)->format('H:i') : '') }}" :error="$errors->first('start_time')" />
        <x-ui.input label="End Time" name="end_time" type="time" value="{{ old('end_time', $event?->end_time ? \Carbon\Carbon::parse($event->end_time)->format('H:i') : '') }}" :error="$errors->first('end_time')" />
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Location</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Venue" name="venue" value="{{ old('venue', $event->venue ?? '') }}" :error="$errors->first('venue')" />
            <x-ui.input label="Organizer" name="organizer" value="{{ old('organizer', $event->organizer ?? '') }}" :error="$errors->first('organizer')" />
        </div>

        <x-ui.input label="Address" name="address" value="{{ old('address', $event->address ?? '') }}" :error="$errors->first('address')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="City" name="city" value="{{ old('city', $event->city ?? '') }}" :error="$errors->first('city')" />
            <x-ui.input label="State" name="state" value="{{ old('state', $event->state ?? '') }}" :error="$errors->first('state')" />
        </div>

        <x-ui.input label="Google Map Location" name="map_url" value="{{ old('map_url', $event->map_url ?? '') }}" helper="Paste a Google Maps share link or embed URL." :error="$errors->first('map_url')" />
    </div>
</x-ui.card>

<x-ui.card x-data="{ requiresRegistration: {{ old('requires_registration', $event->requires_registration ?? false) ? 'true' : 'false' }} }">
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Registration</h3>

    <div class="mt-4 space-y-5">
        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="requires_registration" value="1" x-model="requiresRegistration" class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Visitors must register to attend this event
        </label>

        <div x-show="requiresRegistration" x-cloak class="max-w-xs">
            <x-ui.input label="Maximum Participants" name="max_participants" type="number" min="1" value="{{ old('max_participants', $event->max_participants ?? '') }}" helper="Leave blank for unlimited seats." :error="$errors->first('max_participants')" />
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Images</h3>

    <div class="mt-4 space-y-6">
        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Featured Image @if (! $event) <span class="text-error-600">*</span> @endif</p>
            @if ($featuredImage)
                <img src="{{ $featuredImage->getUrl() }}" alt="" class="mb-2 h-32 w-full max-w-sm rounded-md object-cover">
                <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="remove_featured_image" value="1" class="rounded border-border-subtle text-error-600">
                    Remove current featured image
                </label>
            @endif
            <input type="file" name="featured_image" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('featured_image')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Gallery Images</p>

            @if ($galleryImages->isNotEmpty())
                <div class="mb-3 grid grid-cols-2 gap-3 sm:grid-cols-4">
                    @foreach ($galleryImages as $media)
                        <div>
                            <img src="{{ $media->getUrl() }}" alt="" class="h-24 w-full rounded-md object-cover">
                            <label class="mt-1 flex items-center gap-1.5 text-xs text-text-600 dark:text-night-text-muted">
                                <input type="checkbox" name="remove_gallery_ids[]" value="{{ $media->id }}" class="rounded border-border-subtle text-error-600">
                                Remove
                            </label>
                        </div>
                    @endforeach
                </div>
            @endif

            <p class="mb-1.5 text-xs text-text-400 dark:text-night-text-muted">Add more images:</p>
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
            @if ($event?->getFirstMedia('og_image'))
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

        <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', $seo->schema_type ?? 'Event') }}" helper="e.g. Event, SocialEvent, FoodEvent." :error="$errors->first('schema_type')" />
    </div>
</x-ui.card>
