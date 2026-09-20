{{--
    Server-rendered SVG bar chart — no JavaScript, no chart library.
    $data: [label => number]. $money formats values as ₹. The full data
    is also emitted as a visually-hidden table so screen readers and
    copy/paste get real numbers, not a picture.
--}}
@props(['data' => [], 'title', 'money' => false, 'color' => 'text-primary-700', 'height' => 220])

@php
    $data = collect($data);
    $max = max(1, (float) $data->max());
    $count = max(1, $data->count());
    $width = 720;
    $padLeft = 8;
    $padBottom = 28;
    $chartHeight = $height - $padBottom;
    $slot_ = ($width - $padLeft) / $count;
    $barWidth = max(4, $slot_ * 0.62);
    $format = fn ($v) => $money ? format_inr((float) $v) : number_format((float) $v);
    $chartId = 'chart-'.\Illuminate\Support\Str::random(6);
    $labelEvery = $count > 14 ? (int) ceil($count / 12) : 1;
@endphp

<figure {{ $attributes->class(['w-full']) }}>
    @if ($data->isEmpty() || $data->sum() == 0)
        <div class="flex h-40 items-center justify-center rounded-md border border-dashed border-border-subtle text-sm text-text-400 dark:border-night-border dark:text-night-text-muted">
            No data for this period.
        </div>
    @else
        <svg viewBox="0 0 {{ $width }} {{ $height }}" role="img" aria-labelledby="{{ $chartId }}-title {{ $chartId }}-desc" class="h-auto w-full {{ $color }}" preserveAspectRatio="xMidYMid meet">
            <title id="{{ $chartId }}-title">{{ $title }}</title>
            <desc id="{{ $chartId }}-desc">Bar chart. Highest value {{ $format($max) }}. Full figures are in the table below.</desc>
            {{-- gridlines --}}
            @foreach ([0.25, 0.5, 0.75, 1] as $ratio)
                <line x1="{{ $padLeft }}" x2="{{ $width }}" y1="{{ $chartHeight - $chartHeight * $ratio }}" y2="{{ $chartHeight - $chartHeight * $ratio }}" stroke="currentColor" stroke-opacity="0.08" />
            @endforeach
            @foreach ($data as $label => $value)
                @php
                    $i = $loop->index;
                    $h = $max > 0 ? ($value / $max) * ($chartHeight - 8) : 0;
                    $x = $padLeft + $i * $slot_ + ($slot_ - $barWidth) / 2;
                @endphp
                <g>
                    <title>{{ $label }}: {{ $format($value) }}</title>
                    <rect x="{{ round($x, 1) }}" y="{{ round($chartHeight - $h, 1) }}" width="{{ round($barWidth, 1) }}" height="{{ round($h, 1) }}" rx="3" fill="currentColor" fill-opacity="{{ $value > 0 ? 0.85 : 0.15 }}" />
                    @if ($i % $labelEvery === 0)
                        <text x="{{ round($x + $barWidth / 2, 1) }}" y="{{ $height - 8 }}" text-anchor="middle" font-size="11" fill="currentColor" fill-opacity="0.65">{{ \Illuminate\Support\Str::limit($label, 9, '') }}</text>
                    @endif
                </g>
            @endforeach
        </svg>
        <table class="sr-only">
            <caption>{{ $title }}</caption>
            <thead><tr><th scope="col">Period</th><th scope="col">Value</th></tr></thead>
            <tbody>
                @foreach ($data as $label => $value)
                    <tr><th scope="row">{{ $label }}</th><td>{{ $format($value) }}</td></tr>
                @endforeach
            </tbody>
        </table>
    @endif
    <figcaption class="mt-2 flex items-center justify-between text-xs text-text-400 dark:text-night-text-muted">
        <span>{{ $title }}</span>
        @if ($data->sum() > 0)
            <span>Peak {{ $format($max) }}</span>
        @endif
    </figcaption>
</figure>
