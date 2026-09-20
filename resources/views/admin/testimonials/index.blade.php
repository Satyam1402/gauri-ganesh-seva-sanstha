@extends('layouts.admin')

@section('title', 'Testimonials')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Testimonials']]" />
@endsection

@php
    $sortLink = function (string $column, string $label) use ($filters) {
        $isActive = ($filters['sort'] ?? 'created_at') === $column;
        $direction = $isActive && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $url = route('admin.testimonials.index', array_merge($filters, ['sort' => $column, 'direction' => $direction]));
        $arrow = $isActive ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a href="'.e($url).'" class="hover:text-primary-700 dark:hover:text-night-text">'.e($label.$arrow).'</a>';
    };
@endphp

@section('content')
    {{-- Statistics --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total', 'value' => $statistics['total']],
            ['label' => 'Published', 'value' => $statistics['published']],
            ['label' => 'Pending Review', 'value' => $statistics['pending_review']],
            ['label' => 'Featured', 'value' => $statistics['featured']],
            ['label' => 'No Consent', 'value' => $statistics['without_consent']],
            ['label' => 'Archived', 'value' => $statistics['archived']],
        ] as $stat)
            <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $stat['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.testimonials.index') }}" class="flex flex-wrap items-end gap-3" role="search" aria-label="Filter testimonials">
            <div class="w-52">
                <x-ui.input name="q" placeholder="Search name, organisation, text..." value="{{ $filters['q'] ?? '' }}" aria-label="Search testimonials" />
            </div>

            <div class="w-44">
                <x-ui.select name="type" :options="['' => 'All Types'] + $types" :selected="$filters['type'] ?? ''" aria-label="Filter by type" />
            </div>

            <div class="w-40">
                <x-ui.select name="status" :options="['' => 'All Statuses'] + $statuses" :selected="$filters['status'] ?? ''" aria-label="Filter by status" />
            </div>

            <div class="w-36">
                <x-ui.select name="featured" :options="['' => 'Featured?', '1' => 'Featured Only', '0' => 'Not Featured']" :selected="$filters['featured'] ?? ''" aria-label="Filter by featured" />
            </div>

            <div class="w-36">
                <x-ui.select name="consent" :options="['' => 'Consent?', '1' => 'Consented', '0' => 'No Consent']" :selected="$filters['consent'] ?? ''" aria-label="Filter by consent" />
            </div>

            @if (! empty($filters['sort']))
                <input type="hidden" name="sort" value="{{ $filters['sort'] }}">
                <input type="hidden" name="direction" value="{{ $filters['direction'] ?? 'desc' }}">
            @endif

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.testimonials.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.testimonials.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex gap-3">
            @can('manage homepage')
                <x-ui.button href="{{ route('admin.pages.seo.edit', 'testimonials') }}" variant="secondary">Page SEO</x-ui.button>
            @endcan
            @can('create', App\Models\Testimonial::class)
                <x-ui.button href="{{ route('admin.testimonials.create') }}">Add Testimonial</x-ui.button>
            @endcan
        </div>
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $testimonials->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one testimonial first.');
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

            <form method="POST" action="{{ route('admin.testimonials.bulk-status') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="publish">
                <x-ui.button type="submit" size="sm" variant="secondary">Publish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.testimonials.bulk-status') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="unpublish">
                <x-ui.button type="submit" size="sm" variant="secondary">Unpublish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.testimonials.bulk-status') }}" onsubmit="return confirm('Archive the selected testimonials? They will be removed from the website.');" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="archive">
                <x-ui.button type="submit" size="sm" variant="secondary">Archive</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.testimonials.bulk-delete') }}" onsubmit="return confirm('Move the selected testimonials to trash?');" @submit="appendIds($event)">
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
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('name', 'Person') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Testimonial</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('type', 'Type') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('rating', 'Rating') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('status', 'Status') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Consent</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('display_order', 'Order') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($testimonials as $testimonial)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $testimonial->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700" aria-label="Select {{ $testimonial->name }}">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-full bg-primary-100 text-sm font-semibold text-primary-700 dark:bg-night-surface-alt dark:text-night-text">
                                        @if ($photo = $testimonial->getFirstMedia('profile_photo'))
                                            <x-ui.lazy-image :media="$photo" :alt="$testimonial->name" conversion="avatar" />
                                        @else
                                            {{ Str::upper(Str::substr($testimonial->name, 0, 1)) }}
                                        @endif
                                    </div>
                                    <div class="min-w-0">
                                        <a href="{{ route('admin.testimonials.show', $testimonial) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $testimonial->name }}</a>
                                        <p class="truncate text-xs text-text-400 dark:text-night-text-muted">{{ $testimonial->attributionLine() ?? '—' }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="max-w-xs px-4 py-3 text-text-600 dark:text-night-text-muted">
                                <p class="line-clamp-2">{{ $testimonial->content }}</p>
                                @if ($testimonial->testimonialable)
                                    <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">{{ $testimonial->relatedLabel() }}</p>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$testimonial->type->badgeVariant()">{{ $testimonial->type->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">
                                {{ $testimonial->rating ? $testimonial->rating.'/'.App\Models\Testimonial::MAX_RATING : '—' }}
                            </td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$testimonial->status->badgeVariant()">{{ $testimonial->status->label() }}</x-ui.badge>
                                @if ($testimonial->isScheduled())
                                    <span class="mt-1 block text-xs text-text-400 dark:text-night-text-muted">Scheduled {{ $testimonial->published_at->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($testimonial->consent_given)
                                    <span class="text-xs font-medium text-success-600">Yes</span>
                                    @if ($testimonial->consented_at)
                                        <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $testimonial->consented_at->format('d M Y') }}</span>
                                    @endif
                                @else
                                    <span class="text-xs font-medium text-error-600">No</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! $testimonial->trashed())
                                    <form method="POST" action="{{ route('admin.testimonials.order', $testimonial) }}" class="flex items-center gap-1">
                                        @csrf
                                        @method('PATCH')
                                        <label for="order-{{ $testimonial->id }}" class="sr-only">Display order for {{ $testimonial->name }}</label>
                                        <input
                                            id="order-{{ $testimonial->id }}"
                                            type="number"
                                            name="display_order"
                                            min="0"
                                            max="65535"
                                            value="{{ $testimonial->display_order }}"
                                            onchange="this.form.submit()"
                                            class="w-16 rounded-md border border-border-subtle bg-surface-white px-2 py-1 text-sm text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                                        >
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">{{ $testimonial->display_order }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! $testimonial->trashed())
                                    <form method="POST" action="{{ route('admin.testimonials.feature', $testimonial) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded text-lg leading-none focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $testimonial->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured" aria-label="{{ $testimonial->is_featured ? 'Remove from featured' : 'Mark as featured' }}" aria-pressed="{{ $testimonial->is_featured ? 'true' : 'false' }}">
                                            &#9733;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($testimonial->trashed())
                                        <form method="POST" action="{{ route('admin.testimonials.restore', $testimonial) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        @if ($testimonial->isPublished())
                                            <form method="POST" action="{{ route('admin.testimonials.unpublish', $testimonial) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Unpublish</button>
                                            </form>
                                        @elseif ($testimonial->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.testimonials.publish', $testimonial) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text" @if (! $testimonial->consent_given) title="Consent must be recorded before publishing" @endif>Publish</button>
                                            </form>
                                        @endif

                                        @if ($testimonial->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.testimonials.archive', $testimonial) }}" onsubmit="return confirm('Archive the testimonial from {{ addslashes($testimonial->name) }}? It will be removed from the website.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-warning-600 hover:underline">Archive</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.testimonials.edit', $testimonial) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                        <form method="POST" action="{{ route('admin.testimonials.destroy', $testimonial) }}" onsubmit="return confirm('Move the testimonial from {{ addslashes($testimonial->name) }} to trash?');">
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
                            <td colspan="10" class="px-4 py-10 text-center text-text-400 dark:text-night-text-muted">
                                No testimonials found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $testimonials->links() }}
    </div>
@endsection
