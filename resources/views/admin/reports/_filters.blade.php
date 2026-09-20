{{--
    Shared report filter bar: date preset + custom range + report-specific
    selects ($options: [name => [value => label]]). Plain GET form — no JS.
    $labels overrides the select labels per report.
--}}
@php
    $labels = array_merge(['campaign' => 'Campaign', 'category' => 'Category', 'event' => 'Event', 'status' => 'Status', 'method' => 'Payment Method', 'state' => 'State'], $labels ?? []);
@endphp
<form method="GET" action="{{ route('admin.reports.show', $report) }}" class="mb-6 rounded-lg border border-border-subtle bg-surface-white p-4 dark:border-night-border dark:bg-night-surface" role="search" aria-label="Report filters" x-data="{ period: @js($range->preset) }">
    <div class="flex flex-wrap items-end gap-3">
        <div class="w-44">
            <x-ui.select label="Period" name="period" :options="$presets" :selected="$range->preset" x-model="period" />
        </div>
        <div class="w-40" x-show="period === 'custom'" x-cloak>
            <x-ui.input label="From" name="from" type="date" value="{{ $range->isCustom() ? $range->from->toDateString() : '' }}" :error="$errors->first('from')" />
        </div>
        <div class="w-40" x-show="period === 'custom'" x-cloak>
            <x-ui.input label="To" name="to" type="date" value="{{ $range->isCustom() ? $range->to->toDateString() : '' }}" :error="$errors->first('to')" />
        </div>

        @foreach ($options as $name => $choices)
            @continue(empty($choices))
            <div class="w-44">
                <x-ui.select :label="$labels[$name] ?? ucfirst($name)" :name="$name" :options="['' => 'All'] + $choices" :selected="$filters[$name] ?? ''" />
            </div>
        @endforeach

        <x-ui.button type="submit" variant="secondary">Apply</x-ui.button>
        @if ($filters || $range->isCustom())
            <x-ui.button href="{{ route('admin.reports.show', $report) }}" variant="ghost">Reset</x-ui.button>
        @endif

        <div class="ml-auto flex items-center gap-3">
            <span class="text-sm text-text-600 dark:text-night-text-muted">{{ $range->label() }}</span>
            <x-ui.button href="{{ route('admin.reports.export', [$report] + $range->toQuery() + $filters) }}" variant="secondary" size="sm">Export CSV</x-ui.button>
        </div>
    </div>
</form>
