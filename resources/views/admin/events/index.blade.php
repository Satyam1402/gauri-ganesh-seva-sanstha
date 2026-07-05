@extends('layouts.admin')

@section('title', 'Events')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Events']]" />
@endsection

@section('content')
    {{-- Statistics --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total Events', 'value' => $statistics['total']],
            ['label' => 'Published', 'value' => $statistics['published']],
            ['label' => 'Upcoming', 'value' => $statistics['upcoming']],
            ['label' => 'Past', 'value' => $statistics['past']],
            ['label' => 'Registrations', 'value' => $statistics['registrations']],
            ['label' => 'Pending Reg.', 'value' => $statistics['pending_registrations']],
        ] as $stat)
            <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $stat['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.events.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-48">
                <x-ui.input name="q" placeholder="Search title, venue, city..." value="{{ $filters['q'] ?? '' }}" />
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

            <div class="w-40">
                <x-ui.select
                    name="when"
                    :options="['' => 'Any Time', 'upcoming' => 'Upcoming', 'past' => 'Past']"
                    :selected="$filters['when'] ?? ''"
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
                <x-ui.button href="{{ route('admin.events.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.events.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        @can('create', App\Models\Event::class)
            <div class="flex gap-3">
                <x-ui.button href="{{ route('admin.event-registrations.index') }}" variant="secondary">Registrations</x-ui.button>
                <x-ui.button href="{{ route('admin.event-categories.index') }}" variant="secondary">Categories</x-ui.button>
                <x-ui.button href="{{ route('admin.events.create') }}">Add Event</x-ui.button>
            </div>
        @endcan
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $events->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one event first.');
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

            <form method="POST" action="{{ route('admin.events.bulk-publish') }}" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="secondary">Bulk Publish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.events.bulk-delete') }}" onsubmit="return confirm('Delete the selected events?');" @submit="appendIds($event)">
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
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Event</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Category</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">When</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Registrations</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($events as $event)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $event->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md bg-surface-muted dark:bg-night-surface-alt">
                                        <x-ui.lazy-image :media="$event->getFirstMedia('featured_image')" :alt="$event->title" conversion="thumb" />
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-900 dark:text-night-text">{{ $event->title }}</p>
                                        <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $event->locationLine() ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $event->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                {{ $event->dateRange() }}
                                @if ($event->timeRange())
                                    <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $event->timeRange() }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                @if ($event->requires_registration)
                                    <a href="{{ route('admin.event-registrations.index', ['event' => $event->id]) }}" class="text-primary-700 hover:underline dark:text-night-text">
                                        {{ $event->registrations_count }}@if ($event->max_participants) / {{ $event->max_participants }}@endif
                                    </a>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">Not required</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$event->status->badgeVariant()">{{ $event->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                @if (! $event->trashed())
                                    <form method="POST" action="{{ route('admin.events.feature', $event) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-lg leading-none {{ $event->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured">
                                            &#9733;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($event->trashed())
                                        <form method="POST" action="{{ route('admin.events.restore', $event) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        @if ($event->status->value === 'published')
                                            <form method="POST" action="{{ route('admin.events.unpublish', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Unpublish</button>
                                            </form>
                                            <form method="POST" action="{{ route('admin.events.cancel', $event) }}" onsubmit="return confirm('Cancel {{ $event->title }}? It stays visible with a cancelled notice.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-warning-600 hover:underline">Cancel</button>
                                            </form>
                                        @else
                                            <form method="POST" action="{{ route('admin.events.publish', $event) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Publish</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.events.edit', $event) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                        <form method="POST" action="{{ route('admin.events.destroy', $event) }}" onsubmit="return confirm('Delete {{ $event->title }}?');">
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
                            <td colspan="8" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                                No events found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $events->links() }}
    </div>
@endsection
