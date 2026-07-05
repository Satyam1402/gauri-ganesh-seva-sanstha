@extends('layouts.admin')

@section('title', 'Donation Campaigns')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Campaigns']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.donation-campaigns.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-56">
                <x-ui.input name="q" placeholder="Search campaigns..." value="{{ $filters['q'] ?? '' }}" />
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
                <x-ui.button href="{{ route('admin.donation-campaigns.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.donation-campaigns.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        @can('create', App\Models\DonationCampaign::class)
            <x-ui.button href="{{ route('admin.donation-campaigns.create') }}">Add Campaign</x-ui.button>
        @endcan
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Campaign</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Progress</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Donations</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Dates</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Featured</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                @forelse ($campaigns as $campaign)
                    <tr>
                        <td class="px-4 py-3">
                            <div class="flex items-center gap-3">
                                <div class="h-12 w-16 shrink-0 overflow-hidden rounded-md bg-surface-muted dark:bg-night-surface-alt">
                                    <x-ui.lazy-image :media="$campaign->getFirstMedia('featured_image')" :alt="$campaign->name" />
                                </div>
                                <div>
                                    <p class="font-medium text-text-900 dark:text-night-text">{{ $campaign->name }}</p>
                                    <p class="text-xs text-text-400 dark:text-night-text-muted">/{{ $campaign->slug }}</p>
                                </div>
                            </div>
                        </td>
                        <td class="px-4 py-3">
                            <p class="font-medium text-text-900 dark:text-night-text">{{ format_inr((float) $campaign->raised_amount) }}</p>
                            @if ($campaign->goal_amount)
                                <div class="mt-1 h-1.5 w-28 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                                    <div class="h-full rounded-full bg-primary-700" style="width: {{ $campaign->progressPercent() }}%"></div>
                                </div>
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">of {{ format_inr((float) $campaign->goal_amount) }} ({{ $campaign->progressPercent() }}%)</p>
                            @else
                                <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">No goal set</p>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $campaign->donations_count }}</td>
                        <td class="px-4 py-3 text-xs text-text-600 dark:text-night-text-muted">
                            {{ $campaign->start_date?->format('d M Y') ?? '—' }}<br>
                            {{ $campaign->end_date?->format('d M Y') ?? 'Ongoing' }}
                        </td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$campaign->status->badgeVariant()">{{ $campaign->status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3">
                            @if (! $campaign->trashed())
                                <form method="POST" action="{{ route('admin.donation-campaigns.feature', $campaign) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="text-lg leading-none {{ $campaign->is_featured ? 'text-accent-500' : 'text-text-300 dark:text-night-text-muted' }}" title="Toggle featured">
                                        &#9733;
                                    </button>
                                </form>
                            @else
                                <span class="text-text-300 dark:text-night-text-muted">—</span>
                            @endif
                        </td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if ($campaign->trashed())
                                    <form method="POST" action="{{ route('admin.donation-campaigns.restore', $campaign) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                    </form>
                                @else
                                    @if ($campaign->status->value === 'active')
                                        <form method="POST" action="{{ route('admin.donation-campaigns.archive', $campaign) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Archive</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.donation-campaigns.activate', $campaign) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Activate</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.donation-campaigns.edit', $campaign) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">Edit</a>

                                    <form method="POST" action="{{ route('admin.donation-campaigns.destroy', $campaign) }}" onsubmit="return confirm('Delete {{ $campaign->name }}?');">
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
                            No campaigns found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $campaigns->links() }}
    </div>
@endsection
