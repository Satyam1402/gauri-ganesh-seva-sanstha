@extends('layouts.admin')

@section('title', 'Gallery Albums')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Gallery']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.gallery-albums.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-52">
                <x-ui.input name="q" placeholder="Search title, location..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-44">
                <x-ui.select
                    name="category"
                    :options="['' => 'All Categories'] + $categories->pluck('name', 'id')->all()"
                    :selected="$filters['category'] ?? ''"
                />
            </div>

            <div class="w-40">
                <x-ui.select
                    name="status"
                    :options="['' => 'All Statuses'] + $statuses"
                    :selected="$filters['status'] ?? ''"
                />
            </div>

            <div class="w-36">
                <x-ui.select
                    name="featured"
                    :options="['' => 'Featured?', '1' => 'Featured Only', '0' => 'Not Featured']"
                    :selected="$filters['featured'] ?? ''"
                />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.gallery-albums.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.gallery-albums.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        @can('create', App\Models\GalleryAlbum::class)
            <div class="flex gap-3">
                <x-ui.button href="{{ route('admin.gallery-categories.index') }}" variant="secondary">Categories</x-ui.button>
                <x-ui.button href="{{ route('admin.gallery-albums.create') }}">Add Album</x-ui.button>
            </div>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Album</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Category</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Media</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Event Date</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                @forelse ($albums as $album)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md bg-surface-muted dark:bg-night-surface-alt">
                                    <x-ui.lazy-image :media="$album->getFirstMedia('cover_image')" :alt="$album->title" conversion="thumb" />
                                </div>
                                <div>
                                    <p class="font-medium text-text-900 dark:text-night-text">{{ $album->title }}</p>
                                    <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $album->location ?? '—' }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $album->category?->name ?? '—' }}</td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                            {{ $album->photos_count }} {{ Str::plural('photo', $album->photos_count) }}
                            @if ($album->videos_count > 0)
                                <span class="text-xs text-text-400 dark:text-night-text-muted">+ {{ $album->videos_count }} {{ Str::plural('video', $album->videos_count) }}</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $album->event_date?->format('d M Y') ?? '—' }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$album->status->badgeVariant()">{{ $album->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @if (! $album->trashed())
                                <form method="POST" action="{{ route('admin.gallery-albums.feature', $album) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-lg leading-none {{ $album->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured">
                                        &#9733;
                                    </button>
                                </form>
                            @else
                                <span class="text-text-300 dark:text-night-text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if ($album->trashed())
                                    <form method="POST" action="{{ route('admin.gallery-albums.restore', $album) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                    </form>
                                @else
                                    @if ($album->status->value === 'published')
                                        <form method="POST" action="{{ route('admin.gallery-albums.unpublish', $album) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Unpublish</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.gallery-albums.publish', $album) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Publish</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.gallery-albums.edit', $album) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Manage</a>

                                    <form method="POST" action="{{ route('admin.gallery-albums.destroy', $album) }}" onsubmit="return confirm('Delete {{ $album->title }}?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-sm text-error-600 hover:underline">Delete</button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                            No albums found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $albums->links() }}
    </div>
@endsection
