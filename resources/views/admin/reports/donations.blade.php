@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-reports.kpi label="Total Raised" :value="$s['total_amount']" money tone="success" hint="Completed payments" />
        <x-reports.kpi label="Donations" :value="$s['donations']" :hint="$s['completed_count'].' completed'" />
        <x-reports.kpi label="Average Gift" :value="$s['average_amount']" money />
        <x-reports.kpi label="Pending" :value="$s['pending_count']" :hint="format_inr($s['pending_amount']).' awaiting'" tone="warning" />
        <x-reports.kpi label="Failed" :value="$s['failed_count']" :tone="$s['failed_count'] ? 'error' : 'neutral'" />
        <x-reports.kpi label="Refunded" :value="$s['refunded_count']" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Donation Trend</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Completed amount per {{ $range->granularity() }}.</p>
            <x-charts.bars :data="$data['trend']" title="Completed donation amount by period" money class="mt-4" />
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Online vs Offline</h2>
            <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Completed amount by channel.</p>
            <x-charts.donut :data="['Online' => $s['online_amount'], 'Offline (bank / UPI)' => $s['offline_amount']]" title="Completed amount online vs offline" money class="mt-4" />
            <dl class="mt-4 grid grid-cols-2 gap-3 text-sm">
                <div><dt class="text-xs uppercase tracking-wide text-text-400 dark:text-night-text-muted">Online</dt><dd class="text-text-900 dark:text-night-text">{{ number_format($s['online_count']) }} donations</dd></div>
                <div><dt class="text-xs uppercase tracking-wide text-text-400 dark:text-night-text-muted">Offline</dt><dd class="text-text-900 dark:text-night-text">{{ number_format($s['offline_count']) }} donations</dd></div>
            </dl>
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Campaign</h2>
            <x-charts.donut :data="$data['byCampaign']->pluck('amount', 'campaign')->all()" title="Completed amount by campaign" money class="mt-4" />
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Status</h2>
            <div class="mt-4">
                @include('admin.reports._breakdown', ['rows' => $data['byStatus'], 'title' => 'Donations by payment status', 'columns' => ['Status', 'Donations', 'Amount']])
            </div>
        </x-ui.card>

        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Payment Method</h2>
            <div class="mt-4">
                @include('admin.reports._breakdown', ['rows' => $data['byMethod'], 'title' => 'Completed donations by payment method', 'columns' => ['Method', 'Donations', 'Amount']])
            </div>
        </x-ui.card>
    </div>

    <x-ui.card class="mt-6">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Campaign Totals</h2>
            @can('viewAny', App\Models\Donation::class)
                <x-ui.button href="{{ route('admin.donations.index') }}" variant="ghost" size="sm">Open donation records →</x-ui.button>
            @endcan
        </div>
        <div class="mt-4">
            @include('admin.reports._breakdown', ['rows' => $data['byCampaign']->mapWithKeys(fn ($r) => [$r['campaign'] => ['count' => $r['count'], 'amount' => $r['amount']]])->all(), 'title' => 'Completed donations by campaign', 'columns' => ['Campaign', 'Donations', 'Amount']])
        </div>
        <p class="mt-3 text-xs text-text-400 dark:text-night-text-muted">This report contains totals only. Donor names, contact details and PAN numbers are available solely on the donation records screen to users with donation permissions.</p>
    </x-ui.card>
@endsection
