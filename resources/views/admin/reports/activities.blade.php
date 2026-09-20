@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-reports.kpi label="Activities" :value="$s['total']" hint="By activity date" />
        <x-reports.kpi label="Published" :value="$s['published']" tone="success" />
        <x-reports.kpi label="Draft / Archived" :value="$s['draft'] + $s['archived']" />
        <x-reports.kpi label="Featured" :value="$s['featured']" tone="accent" />
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Activities by Period</h2>
            <x-charts.bars :data="$data['trend']" title="Activities by period" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Category</h2>
            <x-charts.donut :data="$data['byCategory']" title="Activities by category" class="mt-4" />
        </x-ui.card>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Status</h2>
            <div class="mt-4">@include('admin.reports._breakdown', ['rows' => $data['byStatus'], 'title' => 'Activities by status', 'columns' => ['Status', 'Activities']])</div>
        </x-ui.card>
        <x-ui.card class="lg:col-span-2">
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Recent Activities</h2>
            <ul class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                @forelse ($data['recent'] as $activity)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span>
                            <a href="{{ route('admin.activities.edit', $activity) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $activity->title }}</a>
                            <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $activity->activity_date->format('d M Y') }}{{ $activity->category ? ' · '.$activity->category->name : '' }}</span>
                        </span>
                        <span class="flex items-center gap-2">
                            @if ($activity->is_featured)<x-ui.badge variant="accent">Featured</x-ui.badge>@endif
                            <x-ui.badge :variant="$activity->status->badgeVariant()">{{ $activity->status->label() }}</x-ui.badge>
                        </span>
                    </li>
                @empty
                    <li class="py-6 text-center text-text-400 dark:text-night-text-muted">No activities in this period.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
