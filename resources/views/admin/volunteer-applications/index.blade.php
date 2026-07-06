@extends('layouts.admin')

@section('title', 'Volunteer Applications')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[
        ['label' => 'Dashboard', 'url' => route('admin.dashboard')],
        ['label' => 'Volunteer Applications'],
    ]" />
@endsection

@section('content')
    {{-- Status counters --}}
    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 xl:grid-cols-6">
        @foreach ($statuses as $value => $label)
            <a href="{{ route('admin.volunteer-applications.index', ['status' => $value]) }}"
               class="rounded-lg border border-border-subtle bg-surface-white px-4 py-3 transition hover:border-primary-700/40 dark:border-night-border dark:bg-night-surface">
                <p class="text-xs font-medium uppercase tracking-wide text-text-400 dark:text-night-text-muted">{{ $label }}</p>
                <p class="mt-1 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($statusCounts[$value] ?? 0) }}</p>
            </a>
        @endforeach
    </div>

    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.volunteer-applications.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-48">
                <x-ui.input name="q" placeholder="Search name, email, phone..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-40">
                <x-ui.select name="status" :options="['' => 'All Statuses'] + $statuses" :selected="$filters['status'] ?? ''" />
            </div>

            <div class="w-44">
                <x-ui.select name="availability" :options="['' => 'All Availability'] + $availabilities" :selected="$filters['availability'] ?? ''" />
            </div>

            <div class="w-52">
                <x-ui.select name="interest" :options="['' => 'All Interests'] + $areasOfInterest" :selected="$filters['interest'] ?? ''" />
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
                <x-ui.button href="{{ route('admin.volunteer-applications.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.volunteer-applications.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex gap-3">
            <x-ui.button href="{{ route('admin.volunteer-applications.export', request()->query()) }}" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button href="{{ route('admin.volunteer-applications.export', array_merge(request()->query(), ['format' => 'xlsx'])) }}" variant="secondary">Export Excel</x-ui.button>
        </div>
    </div>

    <div
        x-data="{
            selected: [],
            allIds: {{ $applications->pluck('id')->toJson() }},
            get allChecked() { return this.allIds.length > 0 && this.selected.length === this.allIds.length; },
            toggleAll(checked) { this.selected = checked ? [...this.allIds] : []; },
            appendIds(event) {
                if (this.selected.length === 0) {
                    event.preventDefault();
                    alert('Select at least one application first.');
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

            <form method="POST" action="{{ route('admin.volunteer-applications.bulk-status') }}" @submit="appendIds($event)" class="flex items-center gap-2">
                @csrf
                <x-ui.select name="status" :options="$statuses" selected="under_review" class="!py-1.5 text-sm" />
                <x-ui.button type="submit" size="sm" variant="secondary">Apply Status</x-ui.button>
            </form>

            <form method="POST" action="{{ route('admin.volunteer-applications.bulk-delete') }}" onsubmit="return confirm('Move the selected applications to trash?');" @submit="appendIds($event)">
                @csrf
                <x-ui.button type="submit" size="sm" variant="danger">Bulk Delete</x-ui.button>
            </form>
        </div>

        <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
            <table class="w-full text-left text-sm">
                <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                    <tr>
                        <th class="w-10 px-4 py-3">
                            <input type="checkbox" :checked="allChecked" @change="toggleAll($event.target.checked)" class="rounded border-border-subtle text-primary-700" aria-label="Select all applications">
                        </th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Applicant</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Location</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Interests</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Availability</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Applied</th>
                        <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                        <th class="px-4 py-3"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                    @forelse ($applications as $application)
                        <tr>
                            <td class="px-4 py-3 align-top">
                                <input type="checkbox" value="{{ $application->id }}" x-model="selected" class="rounded border-border-subtle text-primary-700" aria-label="Select {{ $application->fullName() }}">
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-3">
                                    <div class="h-10 w-10 shrink-0 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                                        @if ($photo = $application->getFirstMedia('profile_photo'))
                                            <img src="{{ $photo->getUrl('thumb') }}" alt="{{ $application->fullName() }}" loading="lazy" class="h-full w-full object-cover">
                                        @else
                                            <div class="flex h-full w-full items-center justify-center text-xs font-semibold text-text-400 dark:text-night-text-muted">
                                                {{ strtoupper(substr($application->first_name, 0, 1).substr($application->last_name, 0, 1)) }}
                                            </div>
                                        @endif
                                    </div>
                                    <div>
                                        <p class="font-medium text-text-900 dark:text-night-text">{{ $application->fullName() }}</p>
                                        <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $application->email }} · {{ $application->phone }}</p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $application->city }}, {{ $application->state }}</td>
                            <td class="px-4 py-3">
                                @foreach (array_slice($application->interestLabels(), 0, 2) as $interest)
                                    <x-ui.badge variant="neutral" class="mb-1 mr-1">{{ $interest }}</x-ui.badge>
                                @endforeach
                                @if (count($application->interestLabels()) > 2)
                                    <span class="text-xs text-text-400 dark:text-night-text-muted">+{{ count($application->interestLabels()) - 2 }} more</span>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $application->availability->label() }}</td>
                            <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $application->created_at->format('d M Y') }}</td>
                            <td class="px-4 py-3">
                                <x-ui.badge :variant="$application->status->badgeVariant()">{{ $application->status->label() }}</x-ui.badge>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex items-center justify-end gap-3">
                                    @if ($application->trashed())
                                        <form method="POST" action="{{ route('admin.volunteer-applications.restore', $application) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                        </form>
                                    @else
                                        <a href="{{ route('admin.volunteer-applications.show', $application) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Review</a>

                                        <form method="POST" action="{{ route('admin.volunteer-applications.destroy', $application) }}" onsubmit="return confirm('Move this application to trash?');">
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
                                No volunteer applications found.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="mt-6">
        {{ $applications->links() }}
    </div>
@endsection
