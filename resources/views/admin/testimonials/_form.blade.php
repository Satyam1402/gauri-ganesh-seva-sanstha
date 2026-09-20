{{-- Shared testimonial form fields. $testimonial is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $photo = $testimonial?->getFirstMedia('profile_photo');
    $consentGiven = (bool) old('consent_given', $testimonial->consent_given ?? false);
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Who Is Speaking</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Name" name="name" value="{{ old('name', $testimonial->name ?? '') }}" required maxlength="150" :error="$errors->first('name')" />
            <x-ui.select
                label="Testimonial Type"
                name="type"
                :options="$types"
                :selected="old('type', $testimonial->type?->value ?? 'beneficiary')"
                required
                :error="$errors->first('type')"
            />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="Designation / Relationship" name="designation" value="{{ old('designation', $testimonial->designation ?? '') }}" maxlength="150" helper="e.g. Monthly Donor, Volunteer since 2024, Mother of two." :error="$errors->first('designation')" />
            <x-ui.input label="Organisation" name="organization" value="{{ old('organization', $testimonial->organization ?? '') }}" maxlength="150" helper="Optional." :error="$errors->first('organization')" />
            <x-ui.input label="Location" name="location" value="{{ old('location', $testimonial->location ?? '') }}" maxlength="150" helper="Optional — city or area only, never a full address." :error="$errors->first('location')" />
        </div>

        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Profile Photo</p>
            @if ($photo)
                <div class="mb-2 flex items-center gap-4">
                    <img src="{{ $photo->hasGeneratedConversion('avatar') ? $photo->getUrl('avatar') : $photo->getUrl() }}" alt="Current photo of {{ $testimonial->name }}" class="h-20 w-20 rounded-full object-cover" width="80" height="80">
                    <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                        <input type="checkbox" name="remove_profile_photo" value="1" class="rounded border-border-subtle text-error-600">
                        Remove current photo
                    </label>
                </div>
            @endif
            <label for="profile_photo" class="sr-only">Upload profile photo</label>
            <input id="profile_photo" type="file" name="profile_photo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted" aria-describedby="profile_photo-help">
            <p id="profile_photo-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">JPG, PNG or WebP, up to 2 MB. A square photo works best; it is cropped to a circle. Optional — an initial is shown instead.</p>
            @error('profile_photo')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">The Testimonial</h3>

    <div class="mt-4 space-y-5">
        <div>
            <label for="content" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Testimonial Content <span class="text-error-600" aria-hidden="true">*</span></label>
            <textarea id="content" name="content" rows="6" minlength="20" maxlength="2000" required class="{{ $textareaClasses }}" aria-describedby="content-help">{{ old('content', $testimonial->content ?? '') }}</textarea>
            @error('content')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @else
                <p id="content-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Plain text in the person's own words, 20–2000 characters. Formatting is not supported.</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Rating"
                name="rating"
                :options="$ratingOptions"
                :selected="old('rating', $testimonial->rating ?? '')"
                helper="Optional star rating shown on the card."
                :error="$errors->first('rating')"
            />
            <x-ui.select
                label="Related Activity / Campaign"
                name="related"
                :options="$relatedOptions"
                :selected="old('related', App\Services\TestimonialService::relatedValue($testimonial))"
                helper="Optional — linked testimonials appear first on that activity or campaign page."
                :error="$errors->first('related')"
            />
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Privacy &amp; Consent</h3>
    <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">A testimonial can only be published once the person has agreed to their name, photo and words appearing on the website.</p>

    <div class="mt-4 space-y-5" x-data="{ consent: {{ $consentGiven ? 'true' : 'false' }} }">
        <label class="flex items-start gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="consent_given" value="1" x-model="consent" class="mt-0.5 rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            <span>The person has given consent to publish this testimonial (with their name, photo and details as entered above).</span>
        </label>

        <div x-show="consent" x-cloak class="max-w-xs">
            <x-ui.input label="Consent Date" name="consented_at" type="date" max="{{ now()->toDateString() }}" value="{{ old('consented_at', $testimonial?->consented_at?->format('Y-m-d')) }}" helper="Leave blank to record today's date." :error="$errors->first('consented_at')" />
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Publishing</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $testimonial->status?->value ?? 'draft')"
                helper="Published is downgraded to Pending Review if consent is missing."
                :error="$errors->first('status')"
            />
            <x-ui.input label="Publish Date" name="published_at" type="datetime-local" value="{{ old('published_at', $testimonial?->published_at?->format('Y-m-d\TH:i')) }}" helper="Leave blank to use the moment it is published. A future date schedules it." :error="$errors->first('published_at')" />
            <x-ui.input label="Display Order" name="display_order" type="number" min="0" max="65535" value="{{ old('display_order', $testimonial->display_order ?? 0) }}" helper="Lower numbers appear first." :error="$errors->first('display_order')" />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $testimonial->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this testimonial on the homepage and other featured sections
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Internal Notes</h3>

    <div class="mt-4">
        <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Admin Notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="3" maxlength="2000" placeholder="Visible to the team only — never shown on the website. e.g. how consent was obtained, contact details for follow-up." class="{{ $textareaClasses }}">{{ old('admin_notes', $testimonial->admin_notes ?? '') }}</textarea>
        @error('admin_notes')
            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
        @enderror
    </div>
</x-ui.card>
