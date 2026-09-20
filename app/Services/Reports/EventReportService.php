<?php

namespace App\Services\Reports;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRegistration;
use App\Services\Reports\Concerns\BucketsByPeriod;
use App\Support\Reports\DateRange;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Event and registration aggregates. Registrations are counted only —
 * attendee names, emails and phones never leave the module screens.
 */
class EventReportService
{
    use BucketsByPeriod;

    /**
     * @param  array<string, mixed>  $filters  category (id), event (id), status
     */
    public function report(DateRange $range, array $filters = []): array
    {
        $events = fn () => $this->eventBase($range, $filters);
        $today = now()->toDateString();

        $statusCases = collect(EventStatus::cases())
            ->map(fn (EventStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as status_{$s->value}")
            ->implode(', ');

        $row = $events()->selectRaw(<<<SQL
            COUNT(*) as total,
            SUM(CASE WHEN COALESCE(end_date, start_date) >= '{$today}' THEN 1 ELSE 0 END) as upcoming,
            SUM(CASE WHEN COALESCE(end_date, start_date) < '{$today}' THEN 1 ELSE 0 END) as past,
            {$statusCases}
        SQL)->first();

        $byStatus = [];
        foreach (EventStatus::cases() as $status) {
            $byStatus[$status->label()] = (int) $row->{'status_'.$status->value};
        }

        $eventIds = $events()->pluck('id');
        $registrationSummary = $this->registrationSummary($eventIds, $filters);

        return [
            'summary' => [
                'total' => (int) $row->total,
                'upcoming' => (int) $row->upcoming,
                'past' => (int) $row->past,
                'published' => (int) $row->{'status_'.EventStatus::Published->value},
                'cancelled' => (int) $row->{'status_'.EventStatus::Cancelled->value},
            ] + $registrationSummary,
            'byStatus' => $byStatus,
            'byCategory' => $this->byCategory($events()),
            'trend' => $this->series($events(), 'start_date', $range),
            'registrationTrend' => $eventIds->isEmpty() ? [] : $this->series(EventRegistration::query()->whereIn('event_id', $eventIds)->whereBetween('created_at', $range->bounds()), 'created_at', $range),
            'byEvent' => $this->registrationsByEvent($events()),
        ];
    }

    /**
     * Events starting per period.
     *
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    public function trend(DateRange $range, array $filters = []): array
    {
        return $this->series($this->eventBase($range, $filters), 'start_date', $range);
    }

    /**
     * @param  Collection<int, int>  $eventIds
     * @param  array<string, mixed>  $filters
     * @return array<string, int>
     */
    private function registrationSummary(Collection $eventIds, array $filters): array
    {
        $summary = ['registrations' => 0];
        foreach (RegistrationStatus::cases() as $status) {
            $summary['registrations_'.$status->value] = 0;
        }

        if ($eventIds->isEmpty()) {
            return $summary;
        }

        $cases = collect(RegistrationStatus::cases())
            ->map(fn (RegistrationStatus $s) => "SUM(CASE WHEN status = '{$s->value}' THEN 1 ELSE 0 END) as status_{$s->value}")
            ->implode(', ');

        $row = EventRegistration::query()->whereIn('event_id', $eventIds)->selectRaw("COUNT(*) as total, {$cases}")->first();

        $summary['registrations'] = (int) $row->total;
        foreach (RegistrationStatus::cases() as $status) {
            $summary['registrations_'.$status->value] = (int) $row->{'status_'.$status->value};
        }

        return $summary;
    }

    /**
     * @return array<string, int>
     */
    private function byCategory(Builder $events): array
    {
        $names = EventCategory::query()->pluck('name', 'id');
        $rows = $events->selectRaw('event_category_id, COUNT(*) as aggregate')->groupBy('event_category_id')->orderByDesc('aggregate')->get();

        $result = [];
        foreach ($rows as $row) {
            $label = $row->event_category_id ? ($names[$row->event_category_id] ?? 'Deleted category') : 'Uncategorised';
            $result[$label] = ($result[$label] ?? 0) + (int) $row->aggregate;
        }

        return $result;
    }

    /**
     * Registration counts per event (top 15 by registrations) — one query
     * with conditional counts, no per-event round trips.
     *
     * @return Collection<int, array<string, mixed>>
     */
    private function registrationsByEvent(Builder $events): Collection
    {
        return $events
            ->withCount([
                'registrations',
                'registrations as confirmed_count' => fn ($q) => $q->where('status', RegistrationStatus::Confirmed->value),
                'registrations as attended_count' => fn ($q) => $q->where('status', RegistrationStatus::Attended->value),
                'registrations as cancelled_count' => fn ($q) => $q->where('status', RegistrationStatus::Cancelled->value),
            ])
            ->orderByDesc('registrations_count')
            ->orderByDesc('start_date')
            ->limit(15)
            ->get(['id', 'title', 'slug', 'start_date', 'end_date', 'status', 'max_participants', 'requires_registration'])
            ->map(fn (Event $event) => [
                'id' => $event->id,
                'title' => $event->title,
                'date' => $event->dateRange(),
                'status' => $event->status,
                'capacity' => $event->max_participants,
                'requires_registration' => $event->requires_registration,
                'registrations' => (int) $event->registrations_count,
                'confirmed' => (int) $event->confirmed_count,
                'attended' => (int) $event->attended_count,
                'cancelled' => (int) $event->cancelled_count,
            ]);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return iterable<list<string|int>>
     */
    public function exportRows(DateRange $range, array $filters = []): iterable
    {
        $data = $this->report($range, $filters);

        foreach ($data['summary'] as $metric => $value) {
            yield ['Summary', ucfirst(str_replace('_', ' ', $metric)), $value];
        }

        foreach ($data['byCategory'] as $category => $count) {
            yield ['Category', $category, $count];
        }

        foreach ($data['trend'] as $period => $count) {
            yield ['Events per period', $period, $count];
        }

        foreach ($data['byEvent'] as $event) {
            yield ['Event registrations', $event['title'].' ('.$event['date'].')', $event['registrations']];
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function eventBase(DateRange $range, array $filters): Builder
    {
        return Event::query()
            ->whereBetween('start_date', [$range->from->toDateString(), $range->to->toDateString()])
            ->when(! empty($filters['category']), fn (Builder $q) => $q->where('event_category_id', (int) $filters['category']))
            ->when(! empty($filters['event']), fn (Builder $q) => $q->whereKey((int) $filters['event']))
            ->when(! empty($filters['status']), fn (Builder $q) => $q->where('status', $filters['status']));
    }
}
