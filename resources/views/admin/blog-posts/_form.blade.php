{{-- Shared post form fields. $post is null on create. --}}
@php
    $textareaClasses = 'block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text';
    $seo = $post?->seo;
    $featuredImage = $post?->getFirstMedia('featured_image');
    $galleryImages = $post?->getMedia('gallery') ?? collect();
@endphp

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Post Details</h3>

    <div class="mt-4 space-y-5">
        <x-ui.input label="Title" name="title" value="{{ old('title', $post->title ?? '') }}" required :error="$errors->first('title')" />
        <x-ui.input label="Slug" name="slug" value="{{ old('slug', $post->slug ?? '') }}" helper="Leave blank to auto-generate from the title." :error="$errors->first('slug')" />

        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Category"
                name="blog_category_id"
                :options="['' => 'Uncategorised'] + $categories->pluck('name', 'id')->all()"
                :selected="old('blog_category_id', $post->blog_category_id ?? '')"
                :error="$errors->first('blog_category_id')"
            />
            <x-ui.select
                label="Author"
                name="user_id"
                :options="['' => 'Me ('.auth()->user()->name.')'] + $authors->all()"
                :selected="old('user_id', $post->user_id ?? '')"
                :error="$errors->first('user_id')"
            />
        </div>

        <div>
            <label for="excerpt" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Excerpt</label>
            <textarea id="excerpt" name="excerpt" rows="2" maxlength="500" required class="{{ $textareaClasses }}">{{ old('excerpt', $post->excerpt ?? '') }}</textarea>
            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Short summary shown on listing cards and in search results.</p>
            @error('excerpt')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <div>
            <label for="content" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Content</label>
            <textarea id="content" name="content" rows="16" required class="{{ $textareaClasses }} font-mono text-sm">{{ old('content', $post->content ?? '') }}</textarea>
            <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">
                Supports Markdown: <code># Heading</code>, <code>**bold**</code>, <code>*italic*</code>, <code>- list item</code>, <code>[link](https://...)</code>, <code>&gt; quote</code>. Reading time is calculated automatically.
            </p>
            @error('content')
                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
            @enderror
        </div>

        <x-ui.input label="Tags" name="tags" value="{{ old('tags', $post?->tags->pluck('name')->implode(', ')) }}" helper="Comma-separated, e.g. health, medical camp, pune. New tags are created automatically." :error="$errors->first('tags')" />
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Publishing</h3>

    <div class="mt-4 space-y-5">
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
            <x-ui.select
                label="Status"
                name="status"
                :options="$statuses"
                :selected="old('status', $post->status?->value ?? 'draft')"
                :error="$errors->first('status')"
            />
            <x-ui.input
                label="Publish Date"
                name="published_at"
                type="datetime-local"
                value="{{ old('published_at', $post?->published_at?->format('Y-m-d\TH:i')) }}"
                helper="Set a future date to schedule the post — it goes live automatically."
                :error="$errors->first('published_at')"
            />
        </div>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured ?? false)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Feature this post on the blog page
        </label>

        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="allow_comments" value="1" @checked(old('allow_comments', $post->allow_comments ?? true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Allow comments on this post (moderated — comments appear only after approval)
        </label>
    </div>
</x-ui.card>

<x-ui.card>
    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Images</h3>

    <div class="mt-4 space-y-6">
        <div>
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Featured Image @if (! $post) <span class="text-error-600">*</span> @endif</p>
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
            <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Gallery Images <span class="font-normal text-text-400 dark:text-night-text-muted">(optional)</span></p>

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
            @if ($post?->getFirstMedia('og_image'))
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

        <x-ui.input label="Schema.org Type" name="schema_type" value="{{ old('schema_type', $seo->schema_type ?? 'Article') }}" helper="e.g. Article, NewsArticle, BlogPosting." :error="$errors->first('schema_type')" />
    </div>
</x-ui.card>
