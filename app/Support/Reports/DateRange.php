<?php

namespace App\Support\Reports;

use Illuminate\Support\Carbon;

/**
 * Inclusive reporting window resolved from a preset ("this_month") or a
 * custom from/to pair. Custom ranges are clamped to five years so a
 * mistyped year can't trigger a full-table scan.
 */
final class DateRange
{
    public const PRESETS = [
        'today' => 'Today',
        'yesterday' => 'Yesterday',
        'this_week' => 'This Week',
        'this_month' => 'This Month',
        'last_month' => 'Last Month',
        'this_year' => 'This Year',
        'last_12_months' => 'Last 12 Months',
        'all' => 'All Time',
        'custom' => 'Custom Range',
    ];

    public const MAX_CUSTOM_DAYS = 366 * 5;

    private function __construct(
        public readonly Carbon $from,
        public readonly Carbon $to,
        public readonly string $preset,
    ) {}

    /**
     * @param  array<string, mixed>  $input  Validated request input: period, from, to.
     */
    public static function fromRequest(array $input, string $default = 'this_month'): self
    {
        $preset = array_key_exists($input['period'] ?? '', self::PRESETS) ? $input['period'] : $default;
        $now = Carbon::now();

        [$from, $to] = match ($preset) {
            'today' => [$now->copy()->startOfDay(), $now->copy()->endOfDay()],
            'yesterday' => [$now->copy()->subDay()->startOfDay(), $now->copy()->subDay()->endOfDay()],
            'this_week' => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'this_month' => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'this_year' => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
            'last_12_months' => [$now->copy()->subMonthsNoOverflow(11)->startOfMonth(), $now->copy()->endOfMonth()],
            // Open-ended: reaches forward so scheduled events are included; charts trim empty edge years.
            'all' => [Carbon::create(2000, 1, 1), $now->copy()->addYears(10)->endOfYear()],
            'custom' => self::custom($input['from'] ?? null, $input['to'] ?? null, $now),
        };

        return new self($from, $to, $preset);
    }

    public static function preset(string $preset): self
    {
        return self::fromRequest(['period' => $preset]);
    }

    public function label(): string
    {
        if ($this->preset === 'custom') {
            return $this->from->format('d M Y').' – '.$this->to->format('d M Y');
        }

        return self::PRESETS[$this->preset];
    }

    public function isCustom(): bool
    {
        return $this->preset === 'custom';
    }

    /**
     * Whether the window is wide enough to chart month by month rather
     * than day by day.
     */
    public function spansMonths(): bool
    {
        return $this->from->diffInDays($this->to) > 62;
    }

    /**
     * Whether the window is wide enough to chart year by year.
     */
    public function spansYears(): bool
    {
        return $this->from->diffInDays($this->to) > 366 * 3;
    }

    /**
     * Chart bucket size for this window: day, month or year.
     */
    public function granularity(): string
    {
        return match (true) {
            $this->spansYears() => 'year',
            $this->spansMonths() => 'month',
            default => 'day',
        };
    }

    /**
     * Every year (Y) in the window, oldest first.
     *
     * @return list<string>
     */
    public function yearKeys(): array
    {
        return array_map('strval', range((int) $this->from->format('Y'), (int) $this->to->format('Y')));
    }

    /**
     * Every month bucket (Y-m) in the window, oldest first — used to fill
     * gaps so charts never skip empty months.
     *
     * @return list<string>
     */
    public function monthKeys(): array
    {
        $keys = [];
        $cursor = $this->from->copy()->startOfMonth();

        while ($cursor->lte($this->to)) {
            $keys[] = $cursor->format('Y-m');
            $cursor->addMonthNoOverflow();
        }

        return $keys;
    }

    /**
     * Every day (Y-m-d) in the window, oldest first.
     *
     * @return list<string>
     */
    public function dayKeys(): array
    {
        $keys = [];
        $cursor = $this->from->copy()->startOfDay();

        while ($cursor->lte($this->to)) {
            $keys[] = $cursor->format('Y-m-d');
            $cursor->addDay();
        }

        return $keys;
    }

    /**
     * @return array{0: string, 1: string}
     */
    public function bounds(): array
    {
        return [$this->from->toDateTimeString(), $this->to->toDateTimeString()];
    }

    /**
     * Query-string representation so filters round-trip through links.
     *
     * @return array<string, string>
     */
    public function toQuery(): array
    {
        return $this->preset === 'custom'
            ? ['period' => 'custom', 'from' => $this->from->toDateString(), 'to' => $this->to->toDateString()]
            : ['period' => $this->preset];
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private static function custom(?string $from, ?string $to, Carbon $now): array
    {
        $start = $from ? Carbon::parse($from)->startOfDay() : $now->copy()->startOfMonth();
        $end = $to ? Carbon::parse($to)->endOfDay() : $now->copy()->endOfDay();

        if ($end->lt($start)) {
            [$start, $end] = [$end->copy()->startOfDay(), $start->copy()->endOfDay()];
        }

        if ($start->diffInDays($end) > self::MAX_CUSTOM_DAYS) {
            $start = $end->copy()->subDays(self::MAX_CUSTOM_DAYS)->startOfDay();
        }

        return [$start, $end];
    }
}
