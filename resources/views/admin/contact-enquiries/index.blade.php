@extends('layouts.admin')

@section('title', 'Contact Enquiries')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Contact Enquiries'],
    ]" />
@endsection

@section('content')
    {{-- Status counters --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ($statuses as $value => $label)
            <a href="{{ route('admin.contact-enquiries.index', ['status' => $value]) }}"
               class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 transition hover:border-primary-700/40 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($statusCounts[$value] ?? 0) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.contact-enquiries.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-48">
                <x-ui.input name="q" placeholder="Search name, email, subject..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-44">
                <x-ui.select name="category" :options="['' => 'All Categories'] + $categories" :selected="$filters['category'] ?? ''" />
            </div>

            <div class="w-40">
                <x-ui.select name="status" :options="['' => 'All Statuses'] + $statuses" :selected="$filters['status'] ?? ''" />
            </div>

            <div class="w-44">
                <x-ui.select name="assigned" :options="['' => 'Any Assignee'] + $staff->all()" :selected="$filters['assigned'] ?? ''" />
            </div>

            <div class="w-40">
                <x-ui.input label="From" name="from" type="date" value="{{ $filters['from'] ?? '' }}" />
            </div>
            <div class="w-40">
                <x-ui.input label="To" name="to" type="date" value="{{ $filters['to'] ?? '' }}" />
            </div>

            <div class="w-40">
                <x-ui.select name="sort" :options="['' => 'Sort: Newest', 'oldest' => 'Sort: Oldest', 'name' => 'Sort: Name']" :selected="$filters['sort'] ?? ''" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.contact-enquiries.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.contact-enquiries.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex gap-3">
            <x-ui.button href="{{ route('admin.contact-enquiries.export', request()->query()) }}" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button href="{{ route('admin.contact-enquiries.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" variant="secondary">Export Excel</x-ui.button>
        </div>
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $enquiries->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one enquiry first.');
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
        {{-- Bulk action bar --}}
        <div x-show="selected.length > 0" x-cloak class="mb-4 flex flex-wrap items-center gap-3 rounded-md border border-primary-700/30 bg-primary-100 px-4 py-3 text-sm dark:bg-night-surface-alt">
            <span class="font-medium text-primary-800 dark:text-night-text" x-text="selected.length + ' selected'"></span>

            <form method="POST" action="{{ route('admin.contact-enquiries.bulk-status') }}" @submit="appendIds($event)" class="flex items-center gap-2">
                @csrf
                <x-ui.select name="status" :options="$statuses" selected="in_progress" class="!py-1.5 text-sm" />
                <x-ui.button type="submit" size="sm" variant="secondary">Apply Status</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.contact-enquiries.bulk-delete') }}" onsubmit="return confirm('Move the selected enquiries to trash?');" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="danger">Bulk Delete</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" class="rounded border-border-subtle text-primary-700" aria-label="Select all enquiries">
                        </th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">From</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Subject</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Category</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Assigned</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Received</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($enquiries as $enquiry)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $enquiry->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700" aria-label="Select enquiry from {{ $enquiry->name }}">
                            </td>
                            <td class="px-4 py-3">
                                <p class="font-medium text-text-900 dark:text-night-text">{{ $enquiry->name }}</p>
                                <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $enquiry->email }}{{ $enquiry->phone ? ' · '.$enquiry->phone : '' }}</p>
                            </td>
                            <td class="max-w-56 px-4 py-3 text-text-600 dark:text-night-text-muted">
                                <span class="line-clamp-2">{{ $enquiry->subject }}</span>
                                <span class="mt-0.5 flex items-center gap-2 text-xs text-text-400 dark:text-night-text-muted">
                                    @if ($enquiry->replies_count > 0)
                                        <span>{{ $enquiry->replies_count }} {{ Str::plural('reply', $enquiry->replies_count) }}</span>
                                    @endif
                                    @if ($enquiry->getFirstMedia('attachment'))
                                        <span>📎 attachment</span>
                                    @endif
                                </span>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $enquiry->category->label() }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $enquiry->assignee?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $enquiry->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$enquiry->status->badgeVariant()">{{ $enquiry->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($enquiry->trashed())
                                        <form method="POST" action="{{ route('admin.contact-enquiries.restore', $enquiry) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.contact-enquiries.show', $enquiry) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View</a>

                                        <form method="POST" action="{{ route('admin.contact-enquiries.destroy', $enquiry) }}" onsubmit="return confirm('Move this enquiry to trash?');">
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
                                No enquiries found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $enquiries->links() }}
    </div>
@endsection
