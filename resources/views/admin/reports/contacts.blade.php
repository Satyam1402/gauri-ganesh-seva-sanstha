@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-6">
        <x-reports.kpi label="Enquiries" :value="$s['total']" />
        <x-reports.kpi label="New" :value="$s['new']" :tone="$s['new'] ? 'warning' : 'neutral'" />
        <x-reports.kpi label="In Progress" :value="$s['in_progress']" />
        <x-reports.kpi label="Resolved" :value="$s['resolved']" tone="success" />
        <x-reports.kpi label="Closed" :value="$s['closed']" :hint="$s['archived'].' archived'" />
        <x-reports.kpi label="Spam" :value="$s['spam']" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Enquiries Over Time</h2>
            <x-charts.bars :data="$data['trend']" title="Enquiries by period" color="text-accent-500" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Response Time</h2>
            @if ($data['responseTime'] !== null)
                <p class="mt-4 font-display text-4xl font-semibold text-text-900 dark:text-night-text">{{ $data['responseTime'] }}<span class="text-lg font-normal text-text-400 dark:text-night-text-muted"> hours</span></p>
                <p class="mt-1 text-sm text-text-600 dark:text-night-text-muted">Average time to first reply for enquiries answered in this period.</p>
            @else
                <p class="mt-4 text-sm text-text-400 dark:text-night-text-muted">No replies have been sent for enquiries in this period yet.</p>
            @endif
            <h3 class="mt-6 font-display text-base font-semibold text-text-900 dark:text-night-text">By Status</h3>
            <x-charts.donut :data="collect($s)->except('total')->mapWithKeys(fn ($v, $k) => [App\Enums\EnquiryStatus::from($k)->label() => $v])->all()" title="Enquiries by status" :size="120" class="mt-3" />
        </x-ui.card>
    </div>

    <x-ui.card>
        <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Category</h2>
        <div class="mt-4 grid grid-cols-1 gap-6 lg:grid-cols-2">
            <x-charts.donut :data="$data['byCategory']" title="Enquiries by category" />
            @include('admin.reports._breakdown', ['rows' => $data['byCategory'], 'title' => 'Enquiries by category', 'columns' => ['Category', 'Enquiries']])
        </div>
        <p class="mt-3 text-xs text-text-400 dark:text-night-text-muted">Counts only — messages, sender details and internal notes stay on the enquiries screen.</p>
    </x-ui.card>
@endsection
