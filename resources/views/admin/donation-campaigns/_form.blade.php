{{-- Shared campaign form fields. $campaign is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $seo = $campaign?->seo;
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Campaign Details</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Campaign Name" name="name" value="{{ old('name', $campaign->name ?? '') }}" required :error="$errors->first('name')" />
        <x-ui.input label="Slug" name="slug" value="{{ old('slug', $campaign->slug ?? '') }}" helper="Leave blank to auto-generate from the name." :error="$errors->first('slug')" />

        <div>
            <label for="short_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Short Description</label>
            <textarea id="short_description" name="short_description" rows="2" maxlength="300" class="{{ $textareaClasses }}">{{ old('short_description', $campaign->short_description ?? '') }}</textarea>
            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Shown on listing cards. Max 300 characters.</p>
            @error('short_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="full_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Full Description</label>
            <textarea id="full_description" name="full_description" rows="8" class="{{ $textareaClasses }}">{{ old('full_description', $campaign->full_description ?? '') }}</textarea>
            @error('full_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Fundraising</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="Goal Amount (₹)" name="goal_amount" type="number" step="0.01" min="1" value="{{ old('goal_amount', $campaign->goal_amount ?? '') }}" helper="Leave blank for an open-ended campaign." :error="$errors->first('goal_amount')" />
            <x-ui.input label="Currency" name="currency" value="{{ old('currency', $campaign->currency ?? config('donations.currency')) }}" helper="3-letter ISO code." :error="$errors->first('currency')" />
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $campaign->status?->value ?? 'draft')"
                :error="$errors->first('status')"
            />
        </div>

        @if ($campaign)
            <p class="text-sm text-text-600 dark:text-night-text-muted">
                Raised so far: <strong class="text-text-900 dark:text-night-text">{{ format_inr((float) $campaign->raised_amount) }}</strong>
                (updated automatically from completed donations)
            </p>
        @endif

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="Start Date" name="start_date" type="date" value="{{ old('start_date', $campaign?->start_date?->toDateString()) }}" :error="$errors->first('start_date')" />
            <x-ui.input label="End Date" name="end_date" type="date" value="{{ old('end_date', $campaign?->end_date?->toDateString()) }}" helper="Leave blank for ongoing." :error="$errors->first('end_date')" />
            <x-ui.input label="Display Order" name="order_column" type="number" min="0" value="{{ old('order_column', $campaign->order_column ?? 0) }}" :error="$errors->first('order_column')" />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $campaign->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this campaign
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Featured Image</h3>

    <div class="mt-4 space-y-5">
        @if ($campaign?->getFirstMedia('featured_image'))
            <div class="flex items-center gap-4">
                <div class="h-20 w-32 overflow-hidden rounded-md">
                    <x-ui.lazy-image :media="$campaign->getFirstMedia('featured_image')" :alt="$campaign->name" />
                </div>
                <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="remove_featured_image" value="1" class="rounded border-border-subtle text-primary-700">
                    Remove current image
                </label>
            </div>
        @endif

        <div>
            <input type="file" name="featured_image" accept="image/png,image/jpeg,image/webp" @if(! $campaign) required @endif class="block w-full text-sm text-text-600 dark:text-night-text-muted">
            @error('featured_image')
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
            @if ($campaign?->getFirstMedia('og_image'))
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

        <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', $seo->schema_type ?? 'DonateAction') }}" helper="e.g. DonateAction, Project." :error="$errors->first('schema_type')" />
    </div>
</x-ui.card>
