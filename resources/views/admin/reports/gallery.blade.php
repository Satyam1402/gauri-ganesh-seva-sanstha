@extends('admin.reports._layout')

@section('report')
    @php $s = $data['summary']; @endphp

    <div class="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-4">
        <x-reports.kpi label="Albums" :value="$s['albums']" hint="Created in period" />
        <x-reports.kpi label="Published Albums" :value="$s['published']" tone="success" />
        <x-reports.kpi label="Photos" :value="$s['photos']" :hint="$s['activePhotos'].' visible'" />
        <x-reports.kpi label="Avg Photos / Album" :value="$s['albums'] ? round($s['photos'] / $s['albums'], 1) : 0" />
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-3">
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Category</h2>
            <x-charts.donut :data="$data['byCategory']" title="Albums by category" class="mt-4" />
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">By Status</h2>
            <div class="mt-4">@include('admin.reports._breakdown', ['rows' => $data['byStatus'], 'title' => 'Albums by status', 'columns' => ['Status', 'Albums']])</div>
        </x-ui.card>
        <x-ui.card>
            <h2 class="font-display text-lg font-semibold text-text-900 dark:text-night-text">Recent Albums</h2>
            <ul class="mt-4 divide-y divide-border-subtle text-sm dark:divide-night-border">
                @forelse ($data['recent'] as $album)
                    <li class="flex flex-wrap items-center justify-between gap-2 py-2">
                        <span class="min-w-0">
                            <a href="{{ route('admin.gallery-albums.edit', $album) }}" class="font-medium text-text-900 hover:underline dark:text-night-text">{{ $album->title }}</a>
                            <span class="block text-xs text-text-400 dark:text-night-text-muted">{{ $album->photos_count }} {{ \Illuminate\Support\Str::plural('photo', $album->photos_count) }}{{ $album->category ? ' · '.$album->category->name : '' }}</span>
                        </span>
                        <x-ui.badge :variant="$album->status->badgeVariant()">{{ $album->status->label() }}</x-ui.badge>
                    </li>
                @empty
                    <li class="py-6 text-center text-text-400 dark:text-night-text-muted">No albums created in this period.</li>
                @endforelse
            </ul>
        </x-ui.card>
    </div>
@endsection
