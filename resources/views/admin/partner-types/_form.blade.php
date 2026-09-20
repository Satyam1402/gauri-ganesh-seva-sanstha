{{-- Shared partnership type fields. $type is null on create. --}}
<x-ui.input label="Name" name="name" value="{{ old('name', $type->name ?? '') }}" required maxlength="100" :error="$errors->first('name')" />
<x-ui.input label="Slug" name="slug" value="{{ old('slug', $type->slug ?? '') }}" helper="Leave blank to auto-generate from the name. Used in the public URL (/partners?type=slug) and by page sections that pull a specific category." :error="$errors->first('slug')" />

<div>
    <label for="description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Description</label>
    <textarea id="description" name="description" rows="3" maxlength="1000" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">{{ old('description', $type->description ?? '') }}</textarea>
    @error('description')
        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
    @else
        <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">Optional — shown under the type heading on the partners page.</p>
    @enderror
</div>

<label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
    <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $type->is_active ?? true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
    Visible on the website
</label>
