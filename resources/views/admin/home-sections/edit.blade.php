@extends('layouts.admin')

@section('title', 'Edit '.$section->name)

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Homepage', 'url' => route('admin.home-sections.index')],
        ['label' => $section->name],
    ]" />
@endsection

@php
    $buttonsSeed = $section->buttons->map(fn ($button) => [
        'id' => $button->id,
        'label' => $button->label,
        'url' => $button->url,
        'variant' => $button->variant,
    ])->values();

    $itemsSeed = $section->items->map(fn ($item) => [
        'id' => $item->id,
        'title' => $item->title,
        'subtitle' => $item->subtitle,
        'description' => $item->description,
        'icon' => $item->icon,
        'link_url' => $item->link_url,
        'is_active' => $item->is_active,
        'image_url' => optional($item->getFirstMedia('image'))->getUrl(),
    ])->values();

    $image = $section->getFirstMedia('image');
    $backgroundImage = $section->getFirstMedia('background_image');
@endphp

@section('content')
    <form method="POST" action="{{ route('admin.home-sections.update', $section) }}" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @method('PUT')

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Content</h3>

            <div class="mt-4 space-y-5">
                <x-ui.input label="Heading" name="heading" value="{{ old('heading', $section->heading) }}" :error="$errors->first('heading')" />
                <x-ui.input label="Sub Heading" name="subheading" value="{{ old('subheading', $section->subheading) }}" :error="$errors->first('subheading')" />

                <div>
                    <label for="description" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Description</label>
                    <textarea
                        id="description"
                        name="description"
                        rows="4"
                        class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                    >{{ old('description', $section->description) }}</textarea>
                    @error('description')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Images</h3>

            <div class="mt-4 grid grid-cols-1 gap-6 sm:grid-cols-2">
                <div>
                    <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Image</p>
                    @if ($image)
                        <img src="{{ $image->getUrl() }}" alt="" class="mb-2 h-32 w-full rounded-md object-cover">
                        <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                            <input type="checkbox" name="remove_image" value="1" class="rounded border-border-subtle text-error-600">
                            Remove current image
                        </label>
                    @endif
                    <input type="file" name="image" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-text-600 dark:text-night-text-muted">
                    @error('image')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <p class="mb-1.5 text-sm font-medium text-text-900 dark:text-night-text">Background Image</p>
                    @if ($backgroundImage)
                        <img src="{{ $backgroundImage->getUrl() }}" alt="" class="mb-2 h-32 w-full rounded-md object-cover">
                        <label class="flex items-center gap-2 text-xs text-text-600 dark:text-night-text-muted">
                            <input type="checkbox" name="remove_background_image" value="1" class="rounded border-border-subtle text-error-600">
                            Remove current background image
                        </label>
                    @endif
                    <input type="file" name="background_image" accept="image/png,image/jpeg,image/webp" class="mt-2 block w-full text-sm text-text-600 dark:text-night-text-muted">
                    @error('background_image')
                        <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </x-ui.card>

        @if ($section->key->supportsButtons())
            <x-ui.card x-data="{ buttons: {{ $buttonsSeed->toJson() }} }">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Buttons</h3>
                    <button type="button" @click="buttons.push({ id: null, label: '', url: '', variant: 'primary' })" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                        + Add Button
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="(button, index) in buttons" :key="index">
                        <div class="grid grid-cols-1 gap-3 rounded-md border border-border-subtle p-4 sm:grid-cols-4 dark:border-night-border">
                            <input type="hidden" :name="`buttons[${index}][id]`" :value="button.id">
                            <div class="sm:col-span-1">
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Label</label>
                                <input type="text" :name="`buttons[${index}][label]`" x-model="button.label" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">URL</label>
                                <input type="text" :name="`buttons[${index}][url]`" x-model="button.url" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>
                            <div class="sm:col-span-1">
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Variant</label>
                                <select :name="`buttons[${index}][variant]`" x-model="button.variant" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                    <option value="primary">Primary</option>
                                    <option value="accent">Accent</option>
                                    <option value="secondary">Secondary</option>
                                    <option value="ghost">Ghost</option>
                                    <option value="danger">Danger</option>
                                </select>
                            </div>
                            <div class="flex items-end sm:col-span-1">
                                <button type="button" @click="buttons.splice(index, 1)" class="text-sm text-error-600 hover:underline">Remove</button>
                            </div>
                        </div>
                    </template>

                    <p class="text-xs text-text-400 dark:text-night-text-muted" x-show="buttons.length === 0">No buttons yet — click "Add Button" to create one.</p>
                </div>
            </x-ui.card>
        @endif

        @if ($section->key->supportsItems())
            <x-ui.card x-data="{ items: {{ $itemsSeed->toJson() }} }">
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Items</h3>
                    <button type="button" @click="items.push({ id: null, title: '', subtitle: '', description: '', icon: '', link_url: '', is_active: true, image_url: null })" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">
                        + Add Item
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <template x-for="(item, index) in items" :key="index">
                        <div class="grid grid-cols-1 gap-3 rounded-md border border-border-subtle p-4 sm:grid-cols-2 dark:border-night-border">
                            <input type="hidden" :name="`items[${index}][id]`" :value="item.id">

                            <div>
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Title</label>
                                <input type="text" :name="`items[${index}][title]`" x-model="item.title" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Subtitle</label>
                                <input type="text" :name="`items[${index}][subtitle]`" x-model="item.subtitle" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>
                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Description</label>
                                <textarea :name="`items[${index}][description]`" x-model="item.description" rows="2" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text"></textarea>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Icon</label>
                                <input type="text" :name="`items[${index}][icon]`" x-model="item.icon" placeholder="e.g. heart" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Link URL</label>
                                <input type="text" :name="`items[${index}][link_url]`" x-model="item.link_url" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                            </div>

                            <div class="sm:col-span-2">
                                <label class="mb-1 block text-xs font-medium text-text-600 dark:text-night-text-muted">Image</label>
                                <template x-if="item.image_url">
                                    <img :src="item.image_url" alt="" class="mb-2 h-20 w-32 rounded-md object-cover">
                                </template>
                                <input type="file" :name="`items[${index}][image]`" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                            </div>

                            <div class="flex items-center justify-between sm:col-span-2">
                                <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                                    <input type="checkbox" :name="`items[${index}][is_active]`" value="1" x-model="item.is_active" class="rounded border-border-subtle text-primary-700">
                                    Active
                                </label>
                                <button type="button" @click="items.splice(index, 1)" class="text-sm text-error-600 hover:underline">Remove</button>
                            </div>
                        </div>
                    </template>

                    <p class="text-xs text-text-400 dark:text-night-text-muted" x-show="items.length === 0">No items yet — click "Add Item" to create one.</p>
                </div>
            </x-ui.card>
        @endif

        <div class="flex gap-3">
            <x-ui.button type="submit">Save Changes</x-ui.button>
            <x-ui.button href="{{ route('admin.home-sections.index') }}" variant="ghost">Back to Sections</x-ui.button>
            <x-ui.button href="{{ route('home') }}" target="_blank" variant="secondary">Preview Homepage</x-ui.button>
        </div>
    </form>
@endsection
