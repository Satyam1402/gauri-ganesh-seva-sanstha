@extends('layouts.admin')

@section('title', 'Activities')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Activities']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.activities.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-48">
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

            <div class="w-44">
                <x-ui.select
                    name="sort"
                    :options="['activity_date' => 'Sort: Date', 'title' => 'Sort: Title', 'created_at' => 'Sort: Created', 'status' => 'Sort: Status']"
                    :selected="$filters['sort'] ?? 'activity_date'"
                />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.activities.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.activities.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        @can('create', App\Models\Activity::class)
            <div class="flex gap-3">
                <x-ui.button href="{{ route('admin.activity-categories.index') }}" variant="secondary">Categories</x-ui.button>
                <x-ui.button href="{{ route('admin.activities.create') }}">Add Activity</x-ui.button>
            </div>
        @endcan
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $activities->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one activity first.');
                    return;
                }
                this.selected.forEach(id => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'ids[]';
                    input.value = id;
                    event.target.appendChild(input);
                });
            },
        }"
    >
        <div x-show="selected.length > 0" x-cloak class="mb-4 flex flex-wrap items-center gap-3 rounded-md border border-primary-700/30 bg-primary-100 px-4 py-3 text-sm dark:bg-night-surface-alt">
            <span class="font-medium text-primary-800 dark:text-night-text" x-text="selected.length + ' selected'"></span>

            <form method="POST" action="{{ route('admin.activities.bulk-publish') }}" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="secondary">Bulk Publish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.activities.bulk-category') }}" class="flex items-center gap-2" @submit="appendIds($event)">
                @csrf
                <select name="activity_category_id" required class="rounded-md border border-border-subtle bg-surface-white px-2 py-1.5 text-sm dark:border-night-border dark:bg-night-surface dark:text-night-text">
                    <option value="">Move to category…</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" size="sm" variant="secondary">Apply</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.activities.bulk-delete') }}" onsubmit="return confirm('Delete the selected activities?');" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="danger">Bulk Delete</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" class="rounded border-border-subtle text-primary-700">
                        </th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Activity</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Category</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Date</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($activities as $activity)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $activity->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md bg-surface-muted dark:bg-night-surface-alt">
                                        <x-ui.lazy-image :media="$activity->getFirstMedia('featured_image')" :alt="$activity->title" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-900 dark:text-night-text">{{ $activity->title }}</p>
                                        <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $activity->location }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $activity->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $activity->activity_date->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$activity->status->badgeVariant()">{{ $activity->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                @if (! $activity->trashed())
                                    <form method="POST" action="{{ route('admin.activities.feature', $activity) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-lg leading-none {{ $activity->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured">
                                            &#9733;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($activity->trashed())
                                        <form method="POST" action="{{ route('admin.activities.restore', $activity) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        @if ($activity->status->value === 'published')
                                            <form method="POST" action="{{ route('admin.activities.unpublish', $activity) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Unpublish</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.activities.publish', $activity) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Publish</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.activities.edit', $activity) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                        <form method="POST" action="{{ route('admin.activities.destroy', $activity) }}" onsubmit="return confirm('Delete {{ $activity->title }}?');">
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
                                No activities found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $activities->links() }}
    </div>
@endsection
