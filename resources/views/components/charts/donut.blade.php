{{--
    Server-rendered SVG donut for a distribution. $data: [label => number].
    Segments use a fixed accessible palette; the legend and hidden table
    carry the exact figures.
--}}
@props(['data' => [], 'title', 'money' => false, 'size' => 160])

@php
    $data = collect($data)->filter(fn ($v) => (float) $v > 0)->sortDesc();
    $total = (float) $data->sum();
    $palette = ['#0F5C4E', '#E8912D', '#8A5A2B', '#2F6F9F', '#7A4E8C', '#4F7F3F', '#B3512F', '#5E6A70'];
    $radius = 42;
    $circumference = 2 * M_PI * $radius;
    $offset = 0;
    $format = fn ($v) => $money ? format_inr((float) $v) : number_format((float) $v);
    $chartId = 'donut-'.\Illuminate\Support\Str::random(6);
@endphp

<figure {{ $attributes->class(['w-full']) }}>
    @if ($total <= 0)
        <div class="flex h-40 items-center justify-center rounded-md border border-dashed border-border-subtle text-sm text-text-400 dark:border-night-border dark:text-night-text-muted">
            No data for this period.
        </div>
    @else
        <div class="flex flex-col items-center gap-6 sm:flex-row">
            <svg viewBox="0 0 100 100" width="{{ $size }}" height="{{ $size }}" role="img" aria-labelledby="{{ $chartId }}-title {{ $chartId }}-desc" class="shrink-0">
                <title id="{{ $chartId }}-title">{{ $title }}</title>
                <desc id="{{ $chartId }}-desc">Distribution across {{ $data->count() }} {{ \Illuminate\Support\Str::plural('segment', $data->count()) }}, total {{ $format($total) }}.</desc>
                <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="currentColor" stroke-opacity="0.08" stroke-width="12" />
                @foreach ($data as $label => $value)
                    @php
                        $share = $value / $total;
                        $dash = $share * $circumference;
                    @endphp
                    <circle cx="50" cy="50" r="{{ $radius }}" fill="none" stroke="{{ $palette[$loop->index % count($palette)] }}" stroke-width="12"
                        stroke-dasharray="{{ round($dash, 3) }} {{ round($circumference - $dash, 3) }}" stroke-dashoffset="{{ round(-$offset, 3) }}" transform="rotate(-90 50 50)">
                        <title>{{ $label }}: {{ $format($value) }} ({{ round($share * 100) }}%)</title>
                    </circle>
                    @php $offset += $dash; @endphp
                @endforeach
                <text x="50" y="47" text-anchor="middle" font-size="9" fill="currentColor" fill-opacity="0.6">Total</text>
                <text x="50" y="59" text-anchor="middle" font-size="9" font-weight="600" fill="currentColor">{{ \Illuminate\Support\Str::limit($format($total), 14, '…') }}</text>
            </svg>
            <ul class="w-full space-y-1.5 text-sm" aria-hidden="true">
                @foreach ($data as $label => $value)
                    <li class="flex items-center justify-between gap-3">
                        <span class="flex min-w-0 items-center gap-2 text-text-600 dark:text-night-text-muted">
                            <span class="h-2.5 w-2.5 shrink-0 rounded-full" style="background: {{ $palette[$loop->index % count($palette)] }}"></span>
                            <span class="truncate">{{ $label }}</span>
                        </span>
                        <span class="shrink-0 font-medium text-text-900 dark:text-night-text">{{ $format($value) }} <span class="text-xs font-normal text-text-400 dark:text-night-text-muted">{{ round($value / $total * 100) }}%</span></span>
                    </li>
                @endforeach
            </ul>
        </div>
        <table class="sr-only">
            <caption>{{ $title }}</caption>
            <thead><tr><th scope="col">Segment</th><th scope="col">Value</th><th scope="col">Share</th></tr></thead>
            <tbody>
                @foreach ($data as $label => $value)
                    <tr><th scope="row">{{ $label }}</th><td>{{ $format($value) }}</td><td>{{ round($value / $total * 100) }}%</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <figcaption class="mt-2 text-xs text-text-400 dark:text-night-text-muted">{{ $title }}</figcaption>
</figure>
