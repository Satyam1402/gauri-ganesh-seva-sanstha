@extends('layouts.admin')

@section('title', 'Donation Reports')

@section('breadcrumbs')
    <x-ui.breadcrumbs :items="[['label' => 'Dashboard', 'url' => route('admin.dashboard')], ['label' => 'Donation Reports']]" />
@endsection

@section('content')
    {{-- Revenue summary --}}
    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <x-ui.card>
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Total Raised</p>
            <p class="mt-2 font-display text-2xl font-semibold text-primary-700 dark:text-night-text">{{ format_inr($summary['total']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">This Month</p>
            <p class="mt-2 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ format_inr($summary['this_month']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">This Year</p>
            <p class="mt-2 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ format_inr($summary['this_year']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Donations</p>
            <p class="mt-2 font-display text-2xl font-semibold text-text-900 dark:text-night-text">{{ number_format($summary['count']) }}</p>
            <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Avg {{ format_inr($summary['average']) }}</p>
        </x-ui.card>
        <x-ui.card>
            <p class="text-xs font-semibold uppercase tracking-wide text-text-400 dark:text-night-text-muted">Pending Verification</p>
            <p class="mt-2 font-display text-2xl font-semibold {{ $summary['pending_count'] > 0 ? 'text-warning-600' : 'text-text-900 dark:text-night-text' }}">{{ number_format($summary['pending_count']) }}</p>
        </x-ui.card>
    </div>

    <div class="mt-8 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Monthly donations --}}
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Monthly Donations (Last 12 Months)</h3>

            @php $maxMonthly = max(1, collect($monthly)->max('total')); @endphp
            <div class="mt-5 space-y-2">
                @foreach ($monthly as $row)
                    <div class="flex items-center gap-3 text-sm">
                        <span class="w-20 shrink-0 text-xs text-text-400 dark:text-night-text-muted">{{ $row['month'] }}</span>
                        <div class="h-4 flex-1 overflow-hidden rounded-sm bg-surface-muted dark:bg-night-surface-alt">
                            <div class="h-full rounded-sm bg-primary-700" style="width: {{ round(($row['total'] / $maxMonthly) * 100) }}%"></div>
                        </div>
                        <span class="w-28 shrink-0 text-right text-xs font-medium text-text-900 dark:text-night-text">{{ format_inr($row['total']) }}</span>
                        <span class="w-8 shrink-0 text-right text-xs text-text-400 dark:text-night-text-muted">{{ $row['count'] }}</span>
                    </div>
                @endforeach
            </div>
        </x-ui.card>

        {{-- Top donors --}}
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Top Donors</h3>
            <p class="mt-1 text-xs text-text-400 dark:text-night-text-muted">Completed, non-anonymous donations only.</p>

            @if ($topDonors->isEmpty())
                <p class="mt-5 text-sm text-text-400 dark:text-night-text-muted">No completed donations yet.</p>
            @else
                <table class="mt-4 w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-border-subtle text-xs uppercase tracking-wide text-text-400 dark:border-night-border dark:text-night-text-muted">
                            <th class="py-2 font-semibold">Donor</th>
                            <th class="py-2 text-right font-semibold">Total</th>
                            <th class="py-2 text-right font-semibold">Gifts</th>
                            <th class="py-2 text-right font-semibold">Last Gift</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-border-subtle dark:divide-night-border">
                        @foreach ($topDonors as $donor)
                            <tr>
                                <td class="py-2.5">
                                    <p class="font-medium text-text-900 dark:text-night-text">{{ $donor->donor_name }}</p>
                                    <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $donor->donor_email }}</p>
                                </td>
                                <td class="py-2.5 text-right font-medium text-primary-700 dark:text-night-text">{{ format_inr((float) $donor->total_amount) }}</td>
                                <td class="py-2.5 text-right text-text-600 dark:text-night-text-muted">{{ $donor->donations_count }}</td>
                                <td class="py-2.5 text-right text-xs text-text-400 dark:text-night-text-muted">{{ \Illuminate\Support\Carbon::parse($donor->last_donated_at)->format('d M Y') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @endif
        </x-ui.card>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Campaign progress --}}
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Campaign Progress</h3>

            @if ($campaignProgress->isEmpty())
                <p class="mt-5 text-sm text-text-400 dark:text-night-text-muted">No campaigns created yet.</p>
            @else
                <div class="mt-5 space-y-4">
                    @foreach ($campaignProgress as $campaign)
                        <div>
                            <div class="flex items-center justify-between gap-3 text-sm">
                                <a href="{{ route('admin.donation-campaigns.edit', $campaign) }}" class="font-medium text-text-900 hover:text-primary-700 dark:text-night-text">{{ $campaign->name }}</a>
                                <span class="text-xs text-text-400 dark:text-night-text-muted">
                                    {{ format_inr((float) $campaign->raised_amount) }}{{ $campaign->goal_amount ? ' / '.format_inr((float) $campaign->goal_amount) : '' }}
                                </span>
                            </div>
                            <div class="mt-1.5 h-2 overflow-hidden rounded-full bg-surface-muted dark:bg-night-surface-alt">
                                <div class="h-full rounded-full {{ ($campaign->progressPercent() ?? 0) >= 100 ? 'bg-success-600' : 'bg-primary-700' }}" style="width: {{ $campaign->progressPercent() ?? ($campaign->raised_amount > 0 ? 100 : 0) }}%"></div>
                            </div>
                            <div class="mt-1 flex justify-between text-xs text-text-400 dark:text-night-text-muted">
                                <span>{{ $campaign->status->label() }}</span>
                                <span>{{ $campaign->progressPercent() !== null ? $campaign->progressPercent().'%' : 'No goal' }}</span>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>

        {{-- Recent donations --}}
        <x-ui.card>
            <h3 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Recent Completed Donations</h3>

            @if ($recentDonations->isEmpty())
                <p class="mt-5 text-sm text-text-400 dark:text-night-text-muted">No completed donations yet.</p>
            @else
                <div class="mt-4 divide-y divide-border-subtle dark:divide-night-border">
                    @foreach ($recentDonations as $donation)
                        <div class="flex items-center justify-between gap-3 py-2.5 text-sm">
                            <div>
                                <a href="{{ route('admin.donations.show', $donation) }}" class="font-medium text-text-900 hover:text-primary-700 dark:text-night-text">{{ $donation->donor_name }}</a>
                                <p class="text-xs text-text-400 dark:text-night-text-muted">{{ $donation->campaign?->name ?? 'General' }} &middot; {{ $donation->donated_at->format('d M Y') }}</p>
                            </div>
                            <span class="font-medium text-primary-700 dark:text-night-text">{{ format_inr((float) $donation->amount) }}</span>
                        </div>
                    @endforeach
                </div>
            @endif
        </x-ui.card>
    </div>
@endsection
