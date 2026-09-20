@extends('layouts.admin')

@section('title', 'Partners & Sponsors')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Partners & Sponsors']]" />
@endsection

@php
    $sortLink = function (string $column, string $label) use ($filters) {
        $isActive = ($filters['sort'] ?? 'created_at') === $column;
        $direction = $isActive && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $url = route('admin.partners.index', array_merge($filters, ['sort' => $column, 'direction' => $direction]));
        $arrow = $isActive ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a href="'.e($url).'" class="hover:text-primary-700 dark:hover:text-night-text">'.e($label.$arrow).'</a>';
    };
@endphp

@section('content')
    {{-- Statistics --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total', 'value' => $statistics['total']],
            ['label' => 'Active', 'value' => $statistics['active']],
            ['label' => 'Inactive', 'value' => $statistics['inactive']],
            ['label' => 'Draft', 'value' => $statistics['draft']],
            ['label' => 'Featured', 'value' => $statistics['featured']],
            ['label' => 'Archived', 'value' => $statistics['archived']],
        ] as $stat)
            <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $stat['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.partners.index') }}" class="flex flex-wrap items-end gap-3" role="search" aria-label="Filter partners">
            <div class="w-52">
                <x-ui.input name="q" placeholder="Search name, website, city..." value="{{ $filters['q'] ?? '' }}" aria-label="Search partners" />
            </div>

            <div class="w-44">
                <x-ui.select name="type" :options="['' => 'All Types', 'none' => 'No Type'] + $types->pluck('name', 'id')->all()" :selected="$filters['type'] ?? ''" aria-label="Filter by type" />
            </div>

            <div class="w-40">
                <x-ui.select name="status" :options="['' => 'All Statuses'] + $statuses" :selected="$filters['status'] ?? ''" aria-label="Filter by status" />
            </div>

            <div class="w-36">
                <x-ui.select name="featured" :options="['' => 'Featured?', '1' => 'Featured Only', '0' => 'Not Featured']" :selected="$filters['featured'] ?? ''" aria-label="Filter by featured" />
            </div>

            @if (! empty($filters['sort']))
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <input type="hidden" name="direction" value="{{ $filters['direction'] ?? 'desc' }}">
            @endif

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.partners.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.partners.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex flex-wrap gap-3">
            @can('manage homepage')
                <x-ui.button href="{{ route('admin.pages.seo.edit', 'partners') }}" variant="secondary">Page SEO</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('admin.partner-types.index') }}" variant="secondary">Types</x-ui.button>
            @can('create', App\Models\Partner::class)
                <x-ui.button href="{{ route('admin.partners.create') }}">Add Partner</x-ui.button>
            @endcan
        </div>
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $partners->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one partner first.');
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
        <div x-show="selected.length > 0" x-cloak class="mb-4 flex flex-wrap items-center gap-3 rounded-md border border-primary-700/30 bg-primary-100 px-4 py-3 text-sm dark:bg-night-surface-alt" role="toolbar" aria-label="Bulk actions">
            <span class="font-medium text-primary-800 dark:text-night-text" x-text="selected.length + ' selected'"></span>

            <form method="POST" action="{{ route('admin.partners.bulk-update') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="activate">
                <x-ui.button type="submit" size="sm" variant="secondary">Activate</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.partners.bulk-update') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="deactivate">
                <x-ui.button type="submit" size="sm" variant="secondary">Deactivate</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.partners.bulk-update') }}" onsubmit="return confirm('Archive the selected partners? They will be removed from the website.');" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="archive">
                <x-ui.button type="submit" size="sm" variant="secondary">Archive</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.partners.bulk-delete') }}" onsubmit="return confirm('Move the selected partners to trash?');" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="danger">Delete</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th scope="col" class="w-10 px-4 py-3">
                            <input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" class="rounded border-border-subtle text-primary-700" aria-label="Select all on this page">
                        </th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('name', 'Partner') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Type</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Website</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('started_on', 'Since') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('status', 'Status') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('display_order', 'Order') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($partners as $partner)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $partner->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700" aria-label="Select {{ $partner->name }}">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-12 w-24 shrink-0 items-center justify-center overflow-hidden rounded-md border border-border-subtle bg-surface-white p-1 dark:border-night-border dark:bg-night-surface-alt">
                                        @if ($logo = $partner->getFirstMedia('logo'))
                                            <img src="{{ $logo->hasGeneratedConversion('thumb') ? $logo->getUrl('thumb') : $logo->getUrl() }}" alt="{{ $partner->logoAlt() }}" width="96" height="48" loading="lazy" class="max-h-full max-w-full object-contain">
                                        @else
                                            <span class="text-[10px] uppercase tracking-wide text-text-400 dark:text-night-text-muted">No logo</span>
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.partners.show', $partner) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $partner->name }}</a>
                                        <p class="truncate text-xs text-text-400 dark:text-night-text-muted">{{ $partner->locationLine() ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $partner->type?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                @if ($partner->website_url)
                                    <a href="{{ $partner->website_url }}" target="_blank" rel="noopener noreferrer" class="text-primary-700 hover:underline dark:text-night-text">{{ $partner->websiteHost() }}<span class="sr-only"> (opens in a new tab)</span></a>
                                @else
                                    —
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $partner->partnershipPeriod() ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$partner->status->badgeVariant()">{{ $partner->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3">
                                @if (! $partner->trashed())
                                    <form method="POST" action="{{ route('admin.partners.order', $partner) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label for="order-{{ $partner->id }}" class="sr-only">Display order for {{ $partner->name }}</label>
                                        <input
                                            id="order-{{ $partner->id }}"
                                            type="number"
                                            name="display_order"
                                            min="0"
                                            max="65535"
                                            value="{{ $partner->display_order }}"
                                            onchange="this.form.submit()"
                                            class="w-16 rounded-md border border-border-subtle bg-surface-white px-2 py-1 text-sm text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                                        >
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">{{ $partner->display_order }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! $partner->trashed())
                                    <form method="POST" action="{{ route('admin.partners.feature', $partner) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded text-lg leading-none focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $partner->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured" aria-label="{{ $partner->is_featured ? 'Remove from featured' : 'Mark as featured' }}" aria-pressed="{{ $partner->is_featured ? 'true' : 'false' }}">
                                            &#9733;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($partner->trashed())
                                        <form method="POST" action="{{ route('admin.partners.restore', $partner) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        @if ($partner->isActive())
                                            <form method="POST" action="{{ route('admin.partners.deactivate', $partner) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Deactivate</button>
                                            </form>
                                        @elseif ($partner->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.partners.activate', $partner) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Activate</button>
                                            </form>
                                        @endif

                                        @if ($partner->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.partners.archive', $partner) }}" onsubmit="return confirm('Archive {{ addslashes($partner->name) }}? It will be removed from the website.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-warning-600 hover:underline">Archive</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.partners.edit', $partner) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                        <form method="POST" action="{{ route('admin.partners.destroy', $partner) }}" onsubmit="return confirm('Move {{ addslashes($partner->name) }} to trash?');">
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
                            <td colspan="9" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                                No partners found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $partners->links() }}
    </div>
@endsection
