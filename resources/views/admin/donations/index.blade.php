@extends('layouts.admin')

@section('title', 'Donations')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Donations']]" />
@endsection

@section('content')
    <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
        <form method="GET" action="{{ route('admin.donations.index') }}" class="flex flex-wrap items-end gap-3">
            <div class="w-52">
                <x-ui.input name="q" placeholder="Donor, receipt, txn id..." value="{{ $filters['q'] ?? '' }}" />
            </div>

            <div class="w-44">
                <x-ui.select
                    name="campaign"
                    :options="['' => 'All Campaigns'] + $campaigns->pluck('name', 'id')->all()"
                    :selected="$filters['campaign'] ?? ''"
                />
            </div>

            <div class="w-36">
                <x-ui.select
                    name="status"
                    :options="['' => 'All Statuses'] + $statuses"
                    :selected="$filters['status'] ?? ''"
                />
            </div>

            <div class="w-40">
                <x-ui.select
                    name="method"
                    :options="['' => 'All Methods'] + $methods"
                    :selected="$filters['method'] ?? ''"
                />
            </div>

            <div class="w-36">
                <x-ui.input label="From" name="date_from" type="date" value="{{ $filters['date_from'] ?? '' }}" />
            </div>
            <div class="w-36">
                <x-ui.input label="To" name="date_to" type="date" value="{{ $filters['date_to'] ?? '' }}" />
            </div>

            <x-ui.button type="submit" variant="secondary">Filter</x-ui.button>

            @if (! empty($filters['trashed']))
                <input type="hidden" name="trashed" value="1">
                <x-ui.button href="{{ route('admin.donations.index') }}" variant="ghost">View Active</x-ui.button>
            @else
                <x-ui.button href="{{ route('admin.donations.index', ['trashed' => 1]) }}" variant="ghost">View Trashed</x-ui.button>
            @endif
        </form>

        <div class="flex flex-wrap gap-3">
            <x-ui.button href="{{ route('admin.donations.export', array_filter($filters)) }}" variant="secondary">Export CSV</x-ui.button>
            <x-ui.button href="{{ route('admin.donations.export', array_filter($filters) + ['format' => 'xlsx']) }}" variant="secondary">Export Excel</x-ui.button>
            @can('create', App\Models\Donation::class)
                <x-ui.button href="{{ route('admin.donations.create') }}">Record Donation</x-ui.button>
            @endcan
        </div>
    </div>

    <div class="overflow-x-auto rounded-lg border border-border-subtle dark:border-night-border">
        <table class="w-full text-left text-sm">
            <thead class="border-b border-border-subtle bg-surface-muted dark:border-night-border dark:bg-night-surface-alt">
                <tr>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Donor</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Campaign</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Amount</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Method</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Status</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Receipt</th>
                    <th class="px-4 py-3 font-semibold text-text-600 dark:text-night-text-muted">Date</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-border-subtle bg-surface-white dark:divide-night-border dark:bg-night-surface">
                @forelse ($donations as $donation)
                    <tr>
                        <td class="px-4 py-3">
                            <p class="font-medium text-text-900 dark:text-night-text">
                                {{ $donation->donor_name }}
                                @if ($donation->is_anonymous)
                                    <span class="text-xs text-text-400 dark:text-night-text-muted">(anonymous)</span>
                                @endif
                            </p>
                            <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $donation->donor_email }}</p>
                        </td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $donation->campaign?->name ?? 'General' }}</td>
                        <td class="px-4 py-3 font-medium text-text-900 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</td>
                        <td class="px-4 py-3 text-text-600 dark:text-night-text-muted">{{ $donation->payment_method->label() }}</td>
                        <td class="px-4 py-3">
                            <x-ui.badge :variant="$donation->payment_status->badgeVariant()">{{ $donation->payment_status->label() }}</x-ui.badge>
                        </td>
                        <td class="px-4 py-3 text-xs text-text-600 dark:text-night-text-muted">{{ $donation->receipt_number ?? '—' }}</td>
                        <td class="px-4 py-3 text-xs text-text-600 dark:text-night-text-muted">{{ $donation->donated_at->format('d M Y') }}</td>
                        <td class="px-4 py-3 text-right">
                            <div class="flex items-center justify-end gap-3">
                                @if ($donation->trashed())
                                    <form method="POST" action="{{ route('admin.donations.restore', $donation) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="text-sm text-primary-700 hover:underline dark:text-night-text">Restore</button>
                                    </form>
                                @else
                                    @if ($donation->payment_status->value === 'pending')
                                        <form method="POST" action="{{ route('admin.donations.complete', $donation) }}" onsubmit="return confirm('Mark this donation as completed and email the receipt?');">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="text-sm text-success-600 hover:underline">Verify</button>
                                        </form>
                                    @endif

                                    <a href="{{ route('admin.donations.show', $donation) }}" class="text-sm font-medium text-primary-700 hover:underline dark:text-night-text">View</a>
                                    <a href="{{ route('admin.donations.edit', $donation) }}" class="text-sm text-text-600 hover:text-primary-700 dark:text-night-text-muted dark:hover:text-night-text">Edit</a>

                                    <form method="POST" action="{{ route('admin.donations.destroy', $donation) }}" onsubmit="return confirm('Delete this donation record?');">
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
                            No donations found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-6">
        {{ $donations->links() }}
    </div>
@endsection
