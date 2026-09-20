{{-- Shared menu item fields. $item is null on create. --}}
<div x-data="{ linkType: @js(old('link_type', $item?->link_type?->value ?? 'route')) }" class="space-y-5">
    <input type="hidden" name="location" value="{{ $location->value }}">

    <x-ui.input label="Label" name="label" value="{{ old('label', $item->label ?? '') }}" required maxlength="100" :error="$errors->first('label')" />

    <x-ui.select label="Link Type" name="link_type" :options="$linkTypes" :selected="old('link_type', $item?->link_type?->value ?? 'route')" x-model="linkType" :error="$errors->first('link_type')" />

    <div x-show="linkType === 'route'">
        <x-ui.select label="Site Page" name="route_name" :options="['' => 'Choose a page…'] + $routes" :selected="old('route_name', $item->route_name ?? '')" helper="Only public site pages can be linked here." :error="$errors->first('route_name')" />
    </div>

    <div x-show="linkType === 'path'" x-cloak>
        <x-ui.input label="Internal Path" name="url" id="url-path" value="{{ old('url', $item?->link_type?->value === 'path' ? $item->url : '') }}" placeholder="/campaigns" helper="A path on this website, starting with /." :error="$errors->first('url')" x-bind:disabled="linkType !== 'path'" />
    </div>

    <div x-show="linkType === 'url'" x-cloak class="space-y-4">
        <x-ui.input label="External URL" name="url" id="url-external" value="{{ old('url', $item?->link_type?->value === 'url' ? $item->url : '') }}" placeholder="https://" helper="Full https:// address." :error="$errors->first('url')" x-bind:disabled="linkType !== 'url'" />
        <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
            <input type="checkbox" name="open_in_new_tab" value="1" @checked(old('open_in_new_tab', $item->open_in_new_tab ?? true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
            Open in a new tab
        </label>
    </div>

    @if ($location->supportsChildren())
        <x-ui.select label="Parent Item" name="parent_id" :options="['' => 'None (top level)'] + $parents" :selected="old('parent_id', $item->parent_id ?? '')" helper="Choose a parent to place this link in a dropdown." :error="$errors->first('parent_id')" />
    @endif

    <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
        <input type="checkbox" name="is_active" value="1" @checked(old('is_active', $item->is_active ?? true)) class="rounded border-border-subtle text-primary-700 focus:ring-3 focus:ring-primary-700/35">
        Enabled (shown on the website)
    </label>
</div>
