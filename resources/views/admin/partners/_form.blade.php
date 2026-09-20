{{-- Shared partner form fields. $partner is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $logo = $partner?->getFirstMedia('logo');
    $cover = $partner?->getFirstMedia('cover_image');
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Organisation</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Organisation Name" name="name" value="{{ old('name', $partner->name ?? '') }}" required maxlength="150" :error="$errors->first('name')" />
            <x-ui.input label="Slug" name="slug" value="{{ old('slug', $partner->slug ?? '') }}" helper="Leave blank to auto-generate from the name. Used in the public URL." :error="$errors->first('slug')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Partnership Type"
                name="partner_type_id"
                :options="['' => 'No type'] + $types->pluck('name', 'id')->all()"
                :selected="old('partner_type_id', $partner->partner_type_id ?? '')"
                helper="Hidden (inactive) types hide their partners from the website."
                :error="$errors->first('partner_type_id')"
            />
            <x-ui.input label="Website URL" name="website_url" type="url" value="{{ old('website_url', $partner->website_url ?? '') }}" placeholder="https://" helper="Full address including https://. Opens in a new tab on the website." :error="$errors->first('website_url')" />
        </div>

        <div>
            <label for="short_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Short Description</label>
            <textarea id="short_description" name="short_description" rows="2" maxlength="300" class="{{ $textareaClasses }}" aria-describedby="short_description-help">{{ old('short_description', $partner->short_description ?? '') }}</textarea>
            @error('short_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @else
                <p id="short_description-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">One or two sentences shown on cards. Up to 300 characters.</p>
            @enderror
        </div>

        <div>
            <div class="mb-1.5 flex items-center justify-between">
                <label for="full_description" class="block text-sm font-medium text-text-900 dark:text-night-text">Full Description</label>
                <span class="text-xs text-text-400 dark:text-night-text-muted">Markdown supported</span>
            </div>
            <textarea id="full_description" name="full_description" rows="8" maxlength="10000" class="{{ $textareaClasses }}">{{ old('full_description', $partner->full_description ?? '') }}</textarea>
            @error('full_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @else
                <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Optional — shown on the partner's own page. Raw HTML is stripped.</p>
            @enderror
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Logo &amp; Cover</h3>

    <div class="mt-4 grid grid-cols-1 gap-6 md:grid-cols-2">
        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Logo</p>
            @if ($logo)
                <div class="mb-2 flex items-center gap-4">
                    <div class="flex h-20 w-40 items-center justify-center rounded-md border border-border-subtle bg-surface-muted p-2 dark:border-night-border dark:bg-night-surface-alt">
                        <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $partner->logoAlt() }}" width="160" height="80" class="max-h-full max-w-full object-contain">
                    </div>
                    <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                        <input type="checkbox" name="remove_logo" value="1" class="rounded border-border-subtle text-error-600">
                        Remove current logo
                    </label>
                </div>
            @endif
            <label for="logo" class="sr-only">{{ $logo ? 'Replace logo' : 'Upload logo' }}</label>
            <input id="logo" type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted" aria-describedby="logo-help">
            <p id="logo-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">PNG, JPG or WebP up to 2 MB. Transparent PNG on a light background works best; logos are scaled to fit, never cropped.{{ $logo ? ' Uploading a new file replaces the current logo.' : '' }}</p>
            @error('logo')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror

            <div class="mt-4">
                <x-ui.input label="Logo Alt Text" name="logo_alt" value="{{ old('logo_alt', $partner->logo_alt ?? '') }}" maxlength="150" helper="Describes the logo for screen readers. Defaults to “{name} logo”." :error="$errors->first('logo_alt')" />
            </div>
        </div>

        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Cover Image <span class="font-normal text-text-400 dark:text-night-text-muted">(optional)</span></p>
            @if ($cover)
                <img src="{{ $cover->hasGeneratedConversion('thumb') ? $cover->getUrl('thumb') : $cover->getUrl() }}" alt="Current cover image for {{ $partner->name }}" class="mb-2 h-24 w-full max-w-sm rounded-md object-cover">
                <label class="mb-2 flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                    <input type="checkbox" name="remove_cover_image" value="1" class="rounded border-border-subtle text-error-600">
                    Remove current cover image
                </label>
            @endif
            <label for="cover_image" class="sr-only">Upload cover image</label>
            <input id="cover_image" type="file" name="cover_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted" aria-describedby="cover-help">
            <p id="cover-help" class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Wide banner for the partner's page (3:1 works best), up to 5 MB.</p>
            @error('cover_image')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Contact &amp; Location <span class="text-sm font-normal text-text-400 dark:text-night-text-muted">(contact details are never shown publicly)</span></h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Email" name="email" type="email" value="{{ old('email', $partner->email ?? '') }}" maxlength="150" :error="$errors->first('email')" />
            <x-ui.input label="Phone" name="phone" type="tel" value="{{ old('phone', $partner->phone ?? '') }}" maxlength="30" :error="$errors->first('phone')" />
        </div>

        <x-ui.input label="Address" name="address" value="{{ old('address', $partner->address ?? '') }}" maxlength="255" :error="$errors->first('address')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
            <x-ui.input label="City" name="city" value="{{ old('city', $partner->city ?? '') }}" maxlength="100" :error="$errors->first('city')" />
            <x-ui.input label="State" name="state" value="{{ old('state', $partner->state ?? '') }}" maxlength="100" :error="$errors->first('state')" />
            <x-ui.input label="Country" name="country" value="{{ old('country', $partner->country ?? 'India') }}" maxlength="100" :error="$errors->first('country')" />
        </div>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Partnership &amp; Publishing</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.input label="Partnership Start Date" name="started_on" type="date" value="{{ old('started_on', $partner?->started_on?->format('Y-m-d')) }}" :error="$errors->first('started_on')" />
            <x-ui.input label="Partnership End Date" name="ended_on" type="date" value="{{ old('ended_on', $partner?->ended_on?->format('Y-m-d')) }}" helper="Leave blank for ongoing partnerships." :error="$errors->first('ended_on')" />
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $partner->status?->value ?? 'draft')"
                helper="Only Active partners appear on the website."
                :error="$errors->first('status')"
            />
            <x-ui.input label="Display Order" name="display_order" type="number" min="0" max="65535" value="{{ old('display_order', $partner->display_order ?? 0) }}" helper="Lower numbers appear first." :error="$errors->first('display_order')" />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $partner->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this partner on the homepage and in featured sections
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Internal Notes</h3>

    <div class="mt-4">
        <label for="admin_notes" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Admin Notes</label>
        <textarea id="admin_notes" name="admin_notes" rows="3" maxlength="2000" placeholder="Visible to the team only — never shown on the website. e.g. MoU reference, contact person, renewal date." class="{{ $textareaClasses }}">{{ old('admin_notes', $partner->admin_notes ?? '') }}</textarea>
        @error('admin_notes')
            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
        @enderror
    </div>
</x-ui.card>
