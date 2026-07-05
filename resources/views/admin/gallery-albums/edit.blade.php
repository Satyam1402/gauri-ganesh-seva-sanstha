@extends('layouts.admin')

@section('title', 'Manage Album')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Gallery', 'url' => route('admin.gallery-albums.index')],
        ['label' => $album->title],
    ]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <div>
            <h2 class="font-display text-xl font-semibold text-text-900 dark:text-night-text">{{ $album->title }}</h2>
            <div class="mt-1 flex items-center gap-2">
                <x-ui.badge :variant="$album->status->badgeVariant()">{{ $album->status->label() }}</x-ui.badge>
                <span class="text-xs text-text-400 dark:text-night-text-muted">
                    {{ $album->photos->count() }} {{ Str::plural('photo', $album->photos->count()) }},
                    {{ $album->videos->count() }} {{ Str::plural('video', $album->videos->count()) }}
                </span>
            </div>
        </div>

        <div class="flex gap-3">
            @if ($album->status->value === 'published')
                <a href="{{ route('gallery.show', $album) }}" target="_blank" rel="noopener" class="inline-flex items-center text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View on Site ↗</a>
            @endif
            <x-ui.button href="{{ route('admin.gallery-albums.index') }}" variant="ghost">Back to Albums</x-ui.button>
        </div>
    </div>

    {{-- Tabs: details / photos / videos --}}
    <div x-data="{ tab: window.location.hash === '#videos' ? 'videos' : (window.location.hash === '#details' ? 'details' : 'photos') }">
        <div class="mb-6 flex gap-1 border-b border-border-subtle dark:border-night-border">
            <button type="button" @click="tab = 'photos'" :class="tab === 'photos' ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted'" class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Photos ({{ $album->photos->count() }})
            </button>
            <button type="button" @click="tab = 'videos'" :class="tab === 'videos' ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted'" class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Videos ({{ $album->videos->count() }})
            </button>
            <button type="button" @click="tab = 'details'" :class="tab === 'details' ? 'border-primary-700 text-primary-700 dark:text-night-text' : 'border-transparent text-text-600 hover:text-text-900 dark:text-night-text-muted'" class="border-b-2 px-4 py-2.5 text-sm font-medium">
                Album Details
            </button>
        </div>

        {{-- ============ PHOTOS TAB ============ --}}
        <div x-show="tab === 'photos'" x-cloak>
            <x-ui.card
                x-data="{
                    dragging: false,
                    uploading: false,
                    progressText: '',
                    async uploadFiles(files) {
                        if (! files.length) return;
                        this.uploading = true;
                        this.progressText = 'Uploading ' + files.length + ' ' + (files.length === 1 ? 'photo' : 'photos') + '...';
                        const formData = new FormData();
                        Array.from(files).forEach(f => formData.append('photos[]', f));
                        const response = await fetch('{{ route('admin.gallery-photos.store', $album) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                'Accept': 'application/json',
                            },
                            body: formData,
                        });
                        if (response.ok) {
                            window.location.reload();
                        } else {
                            const data = await response.json().catch(() => ({}));
                            this.progressText = data.message ?? 'Upload failed — check file types (JPG, PNG, WebP) and size (max 10 MB each).';
                            this.uploading = false;
                        }
                    },
                }"
            >
                <div
                    @dragover.prevent="dragging = true"
                    @dragleave.prevent="dragging = false"
                    @drop.prevent="dragging = false; uploadFiles($event.dataTransfer.files)"
                    :class="dragging ? 'border-primary-700 bg-primary-700/5' : 'border-border-subtle dark:border-night-border'"
                    class="flex flex-col items-center justify-center rounded-lg border-2 border-dashed px-6 py-10 text-center"
                >
                    <template x-if="! uploading">
                        <div>
                            <p class="font-medium text-text-900 dark:text-night-text">Drag &amp; drop photos here</p>
                            <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">JPG, PNG or WebP — up to 10 MB each, 30 photos per batch.</p>
                            <label class="mt-4 inline-block cursor-pointer rounded-md bg-primary-700 px-4 py-2 text-sm font-medium text-white hover:bg-primary-800">
                                Browse Files
                                <input type="file" multiple accept="image/png,image/jpeg,image/webp" class="hidden" @change="uploadFiles($event.target.files)">
                            </label>
                        </div>
                    </template>
                    <p x-show="uploading" x-text="progressText" class="text-sm font-medium text-primary-700 dark:text-night-text"></p>
                    <p x-show="! uploading && progressText" x-text="progressText" class="mt-3 text-xs text-error-600"></p>
                </div>
            </x-ui.card>

            @if ($album->photos->isEmpty())
                <div class="mt-6">
                    <x-ui.empty-state heading="No photos yet" message="Upload the first photos using the box above." />
                </div>
            @else
                <div
                    class="mt-6"
                    x-data="{
                        selected: [],
                        saving: false,
                        toggleSelect(id) {
                            this.selected.includes(id) ? this.selected = this.selected.filter(i => i !== id) : this.selected.push(id);
                        },
                        async saveOrder() {
                            this.saving = true;
                            const ids = Array.from($refs.grid.children).map(el => el.dataset.id).filter(Boolean);
                            await fetch('{{ route('admin.gallery-photos.reorder', $album) }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                                    'Accept': 'application/json',
                                },
                                body: JSON.stringify({ order: ids }),
                            });
                            this.saving = false;
                        },
                    }"
                >
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <p class="text-sm text-text-600 dark:text-night-text-muted">
                            Drag photos to reorder — order saves automatically.
                            <span x-show="saving" class="text-primary-700">Saving…</span>
                        </p>

                        <form x-show="selected.length > 0" x-cloak method="POST" action="{{ route('admin.gallery-photos.bulk-delete', $album) }}" onsubmit="return confirm('Delete the selected photos? This cannot be undone.');">
                            @csrf
                            <template x-for="id in selected" :key="id">
                                <input type="hidden" name="ids[]" :value="id">
                            </template>
                            <x-ui.button type="submit" variant="danger" size="sm">
                                Delete Selected (<span x-text="selected.length"></span>)
                            </x-ui.button>
                        </form>
                    </div>

                    <div x-ref="grid" @dragend="saveOrder" class="grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                        @foreach ($album->photos as $photo)
                            <div
                                draggable="true"
                                data-id="{{ $photo->id }}"
                                x-data="{ editing: false }"
                                x-on:dragstart="window.__dragEl = $el"
                                x-on:dragover.prevent
                                x-on:drop="
                                    if (window.__dragEl && window.__dragEl !== $el) {
                                        const rect = $el.getBoundingClientRect();
                                        const before = (event.clientX - rect.left) < rect.width / 2;
                                        $el.parentNode.insertBefore(window.__dragEl, before ? $el : $el.nextSibling);
                                    }
                                "
                                class="group relative cursor-move overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:border-night-border dark:bg-night-surface {{ $photo->is_active ? '' : 'opacity-60' }}"
                            >
                                <div class="relative h-36 w-full overflow-hidden bg-surface-muted dark:bg-night-surface-alt">
                                    <x-ui.lazy-image :media="$photo->getFirstMedia('image')" :alt="$photo->resolvedAltText()" conversion="thumb" />

                                    <label class="absolute left-2 top-2 z-10 flex h-6 w-6 cursor-pointer items-center justify-center rounded bg-white/90 shadow dark:bg-night-surface">
                                        <input type="checkbox" :checked="selected.includes({{ $photo->id }})" @change="toggleSelect({{ $photo->id }})" class="rounded border-border-subtle text-primary-700 focus:ring-primary-700/35">
                                    </label>

                                    @unless ($photo->is_active)
                                        <span class="absolute right-2 top-2 rounded bg-text-900/80 px-1.5 py-0.5 text-[10px] font-medium uppercase text-white">Hidden</span>
                                    @endunless
                                </div>

                                <div class="p-2.5">
                                    <p class="truncate text-xs text-text-600 dark:text-night-text-muted" title="{{ $photo->caption }}">{{ $photo->caption ?: 'No caption' }}</p>

                                    <div class="mt-2 flex items-center justify-between gap-1 text-xs">
                                        <button type="button" @click="editing = ! editing" class="font-medium text-primary-700 hover:underline dark:text-night-text">Edit</button>

                                        <form method="POST" action="{{ route('admin.gallery-photos.toggle', [$album, $photo]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-text-600 hover:text-primary-700 dark:text-night-text-muted">{{ $photo->is_active ? 'Hide' : 'Show' }}</button>
                                        </form>

                                        <form method="POST" action="{{ route('admin.gallery-photos.destroy', [$album, $photo]) }}" onsubmit="return confirm('Delete this photo?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-error-600 hover:underline">Delete</button>
                                        </form>
                                    </div>

                                    <form x-show="editing" x-cloak method="POST" action="{{ route('admin.gallery-photos.update', [$album, $photo]) }}" class="mt-3 space-y-2 border-t border-border-subtle pt-3 dark:border-night-border" @dragstart.stop>
                                        @csrf
                                        @method('PUT')
                                        <input type="hidden" name="is_active" value="{{ $photo->is_active ? 1 : 0 }}">
                                        <input type="text" name="caption" value="{{ $photo->caption }}" placeholder="Caption" class="block w-full rounded border border-border-subtle bg-surface-white px-2 py-1.5 text-xs text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                        <input type="text" name="alt_text" value="{{ $photo->alt_text }}" placeholder="Alt text" class="block w-full rounded border border-border-subtle bg-surface-white px-2 py-1.5 text-xs text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                        <input type="text" name="photographer" value="{{ $photo->photographer }}" placeholder="Photographer" class="block w-full rounded border border-border-subtle bg-surface-white px-2 py-1.5 text-xs text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                        <button type="submit" class="w-full rounded bg-primary-700 py-1.5 text-xs font-medium text-white hover:bg-primary-800">Save</button>
                                    </form>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- ============ VIDEOS TAB ============ --}}
        <div x-show="tab === 'videos'" x-cloak class="space-y-6">
            <x-ui.card x-data="{ provider: '{{ old('provider', 'youtube') }}' }">
                <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Add Video</h3>

                <form method="POST" action="{{ route('admin.gallery-videos.store', $album) }}" enctype="multipart/form-data" class="mt-4 space-y-5">
                    @csrf

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <x-ui.input label="Title" name="title" value="{{ old('title') }}" required :error="$errors->first('title')" />
                        <div>
                            <label for="provider" class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Provider</label>
                            <select id="provider" name="provider" x-model="provider" class="block w-full rounded-md border border-border-subtle bg-surface-white px-4 py-2.5 text-base text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                @foreach ($providers as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                            @error('provider')
                                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>

                    <div x-show="provider !== 'self_hosted'">
                        <x-ui.input label="Video URL" name="video_url" value="{{ old('video_url') }}" helper="Paste a YouTube or Vimeo link — the video ID is extracted automatically." :error="$errors->first('video_url')" />
                    </div>

                    <div x-show="provider === 'self_hosted'" x-cloak>
                        <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Video File</label>
                        <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                        <p class="mt-1.5 text-xs text-text-400 dark:text-night-text-muted">MP4, WebM or Ogg — max 100 MB.</p>
                        @error('video_file')
                            <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2">
                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Thumbnail <span class="font-normal text-text-400 dark:text-night-text-muted">(optional)</span></label>
                            <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-text-600 dark:text-night-text-muted">
                            @error('thumbnail')
                                <p class="mt-1.5 text-xs text-error-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <x-ui.input label="Description" name="description" value="{{ old('description') }}" :error="$errors->first('description')" />
                    </div>

                    <x-ui.button type="submit">Add Video</x-ui.button>
                </form>
            </x-ui.card>

            @if ($album->videos->isEmpty())
                <x-ui.empty-state heading="No videos yet" message="Add a YouTube, Vimeo, or self-hosted video above." />
            @else
                <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($album->videos as $video)
                        <div x-data="{ editing: false, provider: '{{ $video->provider->value }}' }" class="overflow-hidden rounded-lg border border-border-subtle bg-surface-white dark:border-night-border dark:bg-night-surface {{ $video->is_active ? '' : 'opacity-60' }}">
                            <div class="relative h-40 w-full bg-text-900">
                                @if ($video->thumbnailUrl())
                                    <img src="{{ $video->thumbnailUrl() }}" alt="{{ $video->title }}" loading="lazy" class="h-full w-full object-cover">
                                @endif
                                <div class="absolute inset-0 flex items-center justify-center">
                                    <span class="flex h-12 w-12 items-center justify-center rounded-full bg-black/60 text-white">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="ml-0.5 h-6 w-6"><path d="M8 5v14l11-7z" /></svg>
                                    </span>
                                </div>
                                <span class="absolute left-2 top-2 rounded bg-black/60 px-1.5 py-0.5 text-[10px] font-medium uppercase text-white">{{ $video->provider->label() }}</span>
                                @unless ($video->is_active)
                                    <span class="absolute right-2 top-2 rounded bg-text-900/80 px-1.5 py-0.5 text-[10px] font-medium uppercase text-white">Hidden</span>
                                @endunless
                            </div>

                            <div class="p-4">
                                <p class="font-medium text-text-900 dark:text-night-text">{{ $video->title }}</p>
                                @if ($video->description)
                                    <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ Str::limit($video->description, 80) }}</p>
                                @endif

                                <div class="mt-3 flex items-center gap-3 text-sm">
                                    <button type="button" @click="editing = ! editing" class="font-medium text-primary-700 hover:underline dark:text-night-text">Edit</button>
                                    <form method="POST" action="{{ route('admin.gallery-videos.destroy', [$album, $video]) }}" onsubmit="return confirm('Remove {{ $video->title }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-error-600 hover:underline">Delete</button>
                                    </form>
                                </div>

                                <form x-show="editing" x-cloak method="POST" action="{{ route('admin.gallery-videos.update', [$album, $video]) }}" enctype="multipart/form-data" class="mt-4 space-y-3 border-t border-border-subtle pt-4 dark:border-night-border">
                                    @csrf
                                    @method('PUT')

                                    <x-ui.input label="Title" name="title" value="{{ $video->title }}" required />

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Provider</label>
                                        <select name="provider" x-model="provider" class="block w-full rounded-md border border-border-subtle bg-surface-white px-3 py-2 text-sm text-text-900 focus:border-primary-700 focus:outline-none dark:border-night-border dark:bg-night-surface dark:text-night-text">
                                            @foreach ($providers as $value => $label)
                                                <option value="{{ $value }}" @selected($video->provider->value === $value)>{{ $label }}</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div x-show="provider !== 'self_hosted'">
                                        <x-ui.input label="Video URL" name="video_url" value="{{ $video->video_url }}" />
                                    </div>

                                    <div x-show="provider === 'self_hosted'" x-cloak>
                                        <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Replace Video File</label>
                                        <input type="file" name="video_file" accept="video/mp4,video/webm,video/ogg" class="block w-full text-xs text-text-600 dark:text-night-text-muted">
                                    </div>

                                    <x-ui.input label="Description" name="description" value="{{ $video->description }}" />

                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-text-900 dark:text-night-text">Replace Thumbnail</label>
                                        <input type="file" name="thumbnail" accept="image/png,image/jpeg,image/webp" class="block w-full text-xs text-text-600 dark:text-night-text-muted">
                                    </div>

                                    <label class="flex items-center gap-2 text-sm text-text-600 dark:text-night-text-muted">
                                        <input type="hidden" name="is_active" value="0">
                                        <input type="checkbox" name="is_active" value="1" @checked($video->is_active) class="rounded border-border-subtle text-primary-700">
                                        Visible on the public gallery
                                    </label>

                                    <x-ui.button type="submit" size="sm">Save Video</x-ui.button>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- ============ DETAILS TAB ============ --}}
        <div x-show="tab === 'details'" x-cloak>
            <form method="POST" action="{{ route('admin.gallery-albums.update', $album) }}" enctype="multipart/form-data" class="space-y-6">
                @csrf
                @method('PUT')

                @include('admin.gallery-albums._form', ['album' => $album])

                <div class="flex gap-3">
                    <x-ui.button type="submit">Save Changes</x-ui.button>
                    <x-ui.button href="{{ route('admin.gallery-albums.index') }}" variant="ghost">Cancel</x-ui.button>
                </div>
            </form>
        </div>
    </div>
@endsection
