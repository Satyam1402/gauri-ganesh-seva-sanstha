{{--
    Shared SEO fields for every content form and the Page SEO screen.

    $seo             App\Models\SeoMeta|null   existing row
    $model           Model|null                owner (image uploads need HasMedia)
    $previewTitle    string                    fallback title when meta_title is blank
    $previewDescription string                 fallback description
    $previewUrl      string                    public URL shown in the previews
    $previewImage    string|null               fallback social image
    $schemaHint      string|null               what structured data this page emits (read-only)

    Previews are approximations — result pages and social cards are
    rendered by third parties and change over time.
--}}
@php
    $seo ??= null;
    $model ??= null;
    $previewImage ??= null;
    $schemaHint ??= null;
    $supportsMedia = $model instanceof \Spatie\MediaLibrary\HasMedia;
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $siteName = setting('general.site_name', config('app.name'));
    $suffix = ' '.setting('seo.title_separator', '—').' '.(setting('seo.title_suffix') ?? $siteName);
    $defaultImage = $previewImage ?? setting_media('branding.og_image', 'webp') ?? setting_media('branding.logo', 'webp');
    $currentOg = $seo?->ogImage?->getUrl();
    $currentTwitter = $seo?->twitterImage?->getUrl();
@endphp

<x-ui.card>
<div
    x-data="{
        title: @js(old('meta_title', $seo?->meta_title ?? '')),
        description: @js(old('meta_description', $seo?->meta_description ?? '')),
        ogTitle: @js(old('og_title', $seo?->og_title ?? '')),
        ogDescription: @js(old('og_description', $seo?->og_description ?? '')),
        fallbackTitle: @js($previewTitle),
        fallbackDescription: @js(\Illuminate\Support\Str::limit(trim(strip_tags((string) $previewDescription)), 160)),
        suffix: @js($suffix),
        get serpTitle() { return this.title.trim() || (this.fallbackTitle + this.suffix); },
        get serpDescription() { return this.description.trim() || this.fallbackDescription; },
        get socialTitle() { return this.ogTitle.trim() || this.title.trim() || this.fallbackTitle; },
        get socialDescription() { return this.ogDescription.trim() || this.serpDescription; },
    }"
>
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">SEO &amp; Sharing</h3>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Leave fields blank to inherit from the content and the global defaults under Settings → SEO.</p>
        </div>
        @if ($schemaHint)
            <x-ui.badge variant="neutral">Structured data: {{ $schemaHint }}</x-ui.badge>
        @endif
    </div>

    {{-- Previews --}}
    <div class="mt-5 grid grid-cols-1 gap-4 lg:grid-cols-2">
        <div class="rounded-lg border border-border-subtle bg-surface-muted p-4 dark:border-night-border dark:bg-night-surface-alt" aria-live="polite">
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Search result preview <span class="font-normal normal-case">(approximate)</span></p>
            <div class="mt-3 rounded-md bg-surface-white p-4 dark:bg-night-surface">
                <p class="truncate text-xs text-text-600 dark:text-night-text-muted">{{ $previewUrl }}</p>
                <p class="mt-1 truncate text-lg text-[#1a0dab] dark:text-[#8ab4f8]" x-text="serpTitle"></p>
                <p class="mt-1 line-clamp-2 text-sm text-text-600 dark:text-night-text-muted" x-text="serpDescription"></p>
            </div>
            <p class="mt-2 text-xs text-text-400 dark:text-night-text-muted">
                Title <span x-text="serpTitle.length"></span>/{{ App\Services\SeoService::TITLE_MAX }} ·
                Description <span x-text="serpDescription.length"></span>/{{ App\Services\SeoService::DESCRIPTION_MAX }}
                <span x-show="serpTitle.length > {{ App\Services\SeoService::TITLE_MAX }} || serpDescription.length > {{ App\Services\SeoService::DESCRIPTION_MAX }}" class="text-warning-600"> — may be truncated</span>
            </p>
        </div>

        <div class="rounded-lg border border-border-subtle bg-surface-muted p-4 dark:border-night-border dark:bg-night-surface-alt" aria-live="polite">
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Social share preview <span class="font-normal normal-case">(approximate)</span></p>
            <div class="mt-3 overflow-hidden rounded-md border border-border-subtle bg-surface-white dark:border-night-border dark:bg-night-surface">
                <div class="flex aspect-[1.91/1] items-center justify-center bg-surface-muted dark:bg-night-surface-alt">
                    @if ($currentOg ?? $defaultImage)
                        <img src="{{ $currentOg ?? $defaultImage }}" alt="" class="h-full w-full object-cover">
                    @else
                        <span class="text-xs text-text-400 dark:text-night-text-muted">No image — upload one below or set a default under Settings → Branding</span>
                    @endif
                </div>
                <div class="p-3">
                    <p class="truncate text-xs uppercase text-text-400 dark:text-night-text-muted">{{ parse_url($previewUrl, PHP_URL_HOST) }}</p>
                    <p class="truncate font-semibold text-text-900 dark:text-night-text" x-text="socialTitle"></p>
                    <p class="line-clamp-2 text-xs text-text-600 dark:text-night-text-muted" x-text="socialDescription"></p>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 space-y-5">
        <x-ui.input label="Meta Title" name="meta_title" x-model="title" value="{{ old('meta_title', $seo?->meta_title ?? '') }}" maxlength="70" helper="Used exactly as entered (no suffix). Leave blank to use the content title." :error="$errors->first('meta_title')" />

        <div>
            <label for="meta_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Meta Description</label>
            <textarea id="meta_description" name="meta_description" rows="2" maxlength="160" x-model="description" class="{{ $textareaClasses }}">{{ old('meta_description', $seo?->meta_description ?? '') }}</textarea>
            @error('meta_description')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @else
                <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">One or two useful sentences, up to 160 characters. No keyword lists.</p>
            @enderror
        </div>

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Search Engine Indexing"
                name="robots"
                :options="['' => 'Inherit default ('.setting('seo.default_robots', 'index, follow').')'] + App\Models\SeoMeta::ROBOTS_OPTIONS"
                :selected="old('robots', $seo?->robots ?? '')"
                helper="Drafts and unpublished content are never indexable regardless of this setting."
                :error="$errors->first('robots')"
            />
            <x-ui.input label="Canonical URL" name="canonical_url" value="{{ old('canonical_url', $seo?->canonical_url ?? '') }}" placeholder="{{ $previewUrl }}" helper="Only set this if another URL is the preferred version of this page." :error="$errors->first('canonical_url')" />
        </div>

        <details class="rounded-md border border-border-subtle dark:border-night-border">
            <summary class="cursor-pointer px-4 py-3 text-sm font-medium text-text-900 dark:text-night-text">Social sharing (Open Graph &amp; X/Twitter)</summary>
            <div class="space-y-5 border-t border-border-subtle px-4 py-4 dark:border-night-border">
                <x-ui.input label="Share Title" name="og_title" x-model="ogTitle" value="{{ old('og_title', $seo?->og_title ?? '') }}" maxlength="70" helper="Defaults to the meta title." :error="$errors->first('og_title')" />

                <div>
                    <label for="og_description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Share Description</label>
                    <textarea id="og_description" name="og_description" rows="2" maxlength="200" x-model="ogDescription" class="{{ $textareaClasses }}">{{ old('og_description', $seo?->og_description ?? '') }}</textarea>
                    @error('og_description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                @if ($supportsMedia)
                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Share Image (Open Graph)</p>
                            @if ($currentOg)
                                <img src="{{ $currentOg }}" alt="Current share image" class="mb-2 h-24 w-full max-w-xs rounded-md object-cover">
                                <label class="mb-2 flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                                    <input type="checkbox" name="remove_og_image" value="1" class="rounded border-border-subtle text-error-600">
                                    Remove current image
                                </label>
                            @endif
                            <label for="og_image" class="sr-only">Upload share image</label>
                            <input id="og_image" type="file" name="og_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">1200×630 recommended. Falls back to the content's featured image, then the site default.</p>
                            @error('og_image')
                                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div>
                            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">X/Twitter Image <span class="font-normal text-text-400 dark:text-night-text-muted">(optional)</span></p>
                            @if ($currentTwitter)
                                <img src="{{ $currentTwitter }}" alt="Current X/Twitter image" class="mb-2 h-24 w-full max-w-xs rounded-md object-cover">
                                <label class="mb-2 flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                                    <input type="checkbox" name="remove_twitter_image" value="1" class="rounded border-border-subtle text-error-600">
                                    Remove current image
                                </label>
                            @endif
                            <label for="twitter_image" class="sr-only">Upload X/Twitter image</label>
                            <input id="twitter_image" type="file" name="twitter_image" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Only needed when X should show a different image from Open Graph.</p>
                            @error('twitter_image')
                                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                @endif

                <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                    <x-ui.select
                        label="X/Twitter Card"
                        name="twitter_card"
                        :options="['summary_large_image' => 'Large image', 'summary' => 'Small summary']"
                        :selected="old('twitter_card', $seo?->twitter_card ?? 'summary_large_image')"
                    />
                    <x-ui.input label="X/Twitter Title" name="twitter_title" value="{{ old('twitter_title', $seo?->twitter_title ?? '') }}" maxlength="70" helper="Defaults to the share title." :error="$errors->first('twitter_title')" />
                    <x-ui.input label="X/Twitter Description" name="twitter_description" value="{{ old('twitter_description', $seo?->twitter_description ?? '') }}" maxlength="200" helper="Defaults to the share description." :error="$errors->first('twitter_description')" />
                </div>
            </div>
        </details>
    </div>
</div>
</x-ui.card>
