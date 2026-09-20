@extends('layouts.admin')

@section('title', 'FAQs')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'FAQs']]" />
@endsection

@php
    $sortLink = function (string $column, string $label) use ($filters) {
        $isActive = ($filters['sort'] ?? 'created_at') === $column;
        $direction = $isActive && ($filters['direction'] ?? 'desc') === 'asc' ? 'desc' : 'asc';
        $url = route('admin.faqs.index', array_merge($filters, ['sort' => $column, 'direction' => $direction]));
        $arrow = $isActive ? (($filters['direction'] ?? 'desc') === 'asc' ? ' ↑' : ' ↓') : '';

        return '<a href="'.e($url).'" class="hover:text-primary-700 dark:hover:text-night-text">'.e($label.$arrow).'</a>';
    };
    $categoryOptions = $categories->pluck('name', 'id')->all();
@endphp

@section('content')
    {{-- Statistics --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        @foreach ([
            ['label' => 'Total', 'value' => $statistics['total']],
            ['label' => 'Published', 'value' => $statistics['published']],
            ['label' => 'Draft', 'value' => $statistics['draft']],
            ['label' => 'Featured', 'value' => $statistics['featured']],
            ['label' => 'Uncategorised', 'value' => $statistics['uncategorised']],
            ['label' => 'Archived', 'value' => $statistics['archived']],
        ] as $stat)
            <div class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $stat['label'] }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($stat['value']) }}</p>
            </div>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.faqs.index') }}" class="flex flex-wrap items-end gap-3" role="search" aria-label="Filter FAQs">
            <div class="w-56">
                <x-ui.input name="q" placeholder="Search questions & answers..." value="{{ $filters['q'] ?? '' }}" aria-label="Search FAQs" />
            </div>

            <div class="w-44">
                <x-ui.select name="category" :options="['' => 'All Categories', 'none' => 'Uncategorised'] + $categoryOptions" :selected="$filters['category'] ?? ''" aria-label="Filter by category" />
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
                <x-ui.button href="{{ route('admin.faqs.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.faqs.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex flex-wrap gap-3">
            @can('manage homepage')
                <x-ui.button href="{{ route('admin.pages.seo.edit', 'faq') }}" variant="secondary">Page SEO</x-ui.button>
            @endcan
            <x-ui.button href="{{ route('admin.faq-categories.index') }}" variant="secondary">Categories</x-ui.button>
            @can('create', App\Models\Faq::class)
                <x-ui.button href="{{ route('admin.faqs.create') }}">Add FAQ</x-ui.button>
            @endcan
        </div>
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $faqs->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one FAQ first.');
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

            <form method="POST" action="{{ route('admin.faqs.bulk-update') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="publish">
                <x-ui.button type="submit" size="sm" variant="secondary">Publish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.faqs.bulk-update') }}" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="unpublish">
                <x-ui.button type="submit" size="sm" variant="secondary">Unpublish</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.faqs.bulk-update') }}" onsubmit="return confirm('Archive the selected FAQs? They will be removed from the website.');" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="archive">
                <x-ui.button type="submit" size="sm" variant="secondary">Archive</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.faqs.bulk-update') }}" class="flex items-center gap-2" @submit="appendIds($event)">
                @csrf
                <input type="hidden" name="action" value="category">
                <label for="bulk-category" class="sr-only">Move to category</label>
                <select id="bulk-category" name="faq_category_id" class="h-9 rounded-md border border-border-subtle bg-surface-white px-2 text-sm text-text-900 dark:border-night-border dark:bg-night-surface dark:text-night-text">
                    <option value="">Uncategorised</option>
                    @foreach ($categoryOptions as $id => $name)
                        <option value="{{ $id }}">{{ $name }}</option>
                    @endforeach
                </select>
                <x-ui.button type="submit" size="sm" variant="secondary">Move</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.faqs.bulk-delete') }}" onsubmit="return confirm('Move the selected FAQs to trash?');" @submit="appendIds($event)">
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
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('question', 'Question') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Category</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('status', 'Status') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('display_order', 'Order') !!}</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                        <th scope="col" class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">{!! $sortLink('created_at', 'Created') !!}</th>
                        <th scope="col" class="px-4 py-3"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($faqs as $faq)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $faq->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700" aria-label="Select {{ $faq->question }}">
                            </td>
                            <td class="max-w-md px-4 py-3">
                                <a href="{{ route('admin.faqs.show', $faq) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $faq->question }}</a>
                                <p class="mt-1 line-clamp-2 text-xs text-text-400 dark:text-night-text-muted">{{ Str::limit($faq->answerText(), 160) }}</p>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $faq->category?->name ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$faq->status->badgeVariant()">{{ $faq->status->label() }}</x-ui.badge>
                                @if ($faq->isScheduled())
                                    <span class="mt-1 block text-xs text-text-400 dark:text-night-text-muted">Scheduled {{ $faq->published_at->format('d M Y') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! $faq->trashed())
                                    <form method="POST" action="{{ route('admin.faqs.order', $faq) }}">
                                        @csrf
                                        @method('PATCH')
                                        <label for="order-{{ $faq->id }}" class="sr-only">Display order for {{ $faq->question }}</label>
                                        <input
                                            id="order-{{ $faq->id }}"
                                            type="number"
                                            name="display_order"
                                            min="0"
                                            max="65535"
                                            value="{{ $faq->display_order }}"
                                            onchange="this.form.submit()"
                                            class="w-16 rounded-md border border-border-subtle bg-surface-white px-2 py-1 text-sm text-text-900 focus:border-primary-700 focus:outline-none focus:ring-3 focus:ring-primary-700/35 dark:border-night-border dark:bg-night-surface dark:text-night-text"
                                        >
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">{{ $faq->display_order }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if (! $faq->trashed())
                                    <form method="POST" action="{{ route('admin.faqs.feature', $faq) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded text-lg leading-none focus:outline-none focus:ring-3 focus:ring-primary-700/35 {{ $faq->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured" aria-label="{{ $faq->is_featured ? 'Remove from featured' : 'Mark as featured' }}" aria-pressed="{{ $faq->is_featured ? 'true' : 'false' }}">
                                            &#9733;
                                        </button>
                                    </form>
                                @else
                                    <span class="text-text-300 dark:text-night-text-muted">—</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-xs text-text-400 dark:text-night-text-muted">{{ $faq->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($faq->trashed())
                                        <form method="POST" action="{{ route('admin.faqs.restore', $faq) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        @if ($faq->isPublished())
                                            <form method="POST" action="{{ route('admin.faqs.unpublish', $faq) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Unpublish</button>
                                            </form>
                                        @elseif ($faq->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.faqs.publish', $faq) }}">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Publish</button>
                                            </form>
                                        @endif

                                        @if ($faq->status->value !== 'archived')
                                            <form method="POST" action="{{ route('admin.faqs.archive', $faq) }}" onsubmit="return confirm('Archive this FAQ? It will be removed from the website.');">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-sm text-warning-600 hover:underline">Archive</button>
                                            </form>
                                        @endif

                                        <a href="{{ route('admin.faqs.edit', $faq) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                        <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Move this FAQ to trash?');">
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
                                No FAQs found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $faqs->links() }}
    </div>
@endsection
