<?php

namespace App\Services\Reports;

use App\Enums\ActivityStatus;
use App\Enums\AlbumStatus;
use App\Enums\CampaignStatus;
use App\Enums\EnquiryStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentStatus;
use App\Enums\PostStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Models\Activity;
use App\Models\BlogPost;
use App\Models\ContactEnquiry;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Models\GalleryAlbum;
use App\Models\VolunteerApplication;
use App\Support\Reports\DateRange;
use Illuminate\Support\Facades\Cache;

/**
 * Dashboard KPIs and trend charts. One conditional-aggregate query per
 * table (nine in total) instead of dozens of counts, cached for five
 * minutes — the figures are organisation-level aggregates, never
 * personal data, so a short shared cache is safe.
 */
class DashboardReportService
{
    public const CACHE_KEY = 'reports.dashboard';

    public const CACHE_MINUTES = 5;

    public function __construct(
        private DonationReportService $donations,
        private VolunteerReportService $volunteers,
        private ContactReportService $contacts,
        private EventReportService $events,
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function overview(bool $fresh = false): array
    {
        if ($fresh) {
            Cache::forget(self::CACHE_KEY);
        }

        return Cache::remember(self::CACHE_KEY, now()->addMinutes(self::CACHE_MINUTES), fn () => [
            'kpis' => $this->kpis(),
            'charts' => $this->charts(),
            'generated_at' => now()->toIso8601String(),
        ]);
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /**
     * @return array<string, int|float>
     */
    private function kpis(): array
    {
        $completed = PaymentStatus::Completed->value;
        $monthStart = now()->startOfMonth()->toDateTimeString();
        $yearStart = now()->startOfYear()->toDateTimeString();
        $today = now()->toDateString();

        $donation = Donation::query()->selectRaw(<<<SQL
            COALESCE(SUM(CASE WHEN payment_status = '{$completed}' THEN amount END), 0) as total_amount,
            SUM(CASE WHEN payment_status = '{$completed}' THEN 1 ELSE 0 END) as total_count,
            COALESCE(SUM(CASE WHEN payment_status = '{$completed}' AND donated_at >= '{$monthStart}' THEN amount END), 0) as month_amount,
            COALESCE(SUM(CASE WHEN payment_status = '{$completed}' AND donated_at >= '{$yearStart}' THEN amount END), 0) as year_amount,
            SUM(CASE WHEN payment_status = '{$completed}' AND donated_at >= '{$monthStart}' THEN 1 ELSE 0 END) as month_count
        SQL)->first();

        $volunteer = VolunteerApplication::query()->selectRaw(<<<SQL
            COUNT(*) as total,
            SUM(CASE WHEN status = '{$this->v(VolunteerApplicationStatus::Pending)}' THEN 1 ELSE 0 END) as pending,
            SUM(CASE WHEN status = '{$this->v(VolunteerApplicationStatus::Approved)}' THEN 1 ELSE 0 END) as approved
        SQL)->first();

        $event = Event::query()->selectRaw(<<<SQL
            COUNT(*) as total,
            SUM(CASE WHEN status = '{$this->v(EventStatus::Published)}' AND COALESCE(end_date, start_date) >= '{$today}' THEN 1 ELSE 0 END) as upcoming
        SQL)->first();

        $enquiry = ContactEnquiry::query()->selectRaw(<<<SQL
            COUNT(*) as total,
            SUM(CASE WHEN status = '{$this->v(EnquiryStatus::New)}' THEN 1 ELSE 0 END) as new_count
        SQL)->first();

        return [
            'donations_total_amount' => (float) $donation->total_amount,
            'donations_total_count' => (int) $donation->total_count,
            'donations_month_amount' => (float) $donation->month_amount,
            'donations_month_count' => (int) $donation->month_count,
            'donations_year_amount' => (float) $donation->year_amount,
            'active_campaigns' => DonationCampaign::query()->where('status', CampaignStatus::Active->value)->count(),
            'volunteers_total' => (int) $volunteer->total,
            'volunteers_pending' => (int) $volunteer->pending,
            'volunteers_approved' => (int) $volunteer->approved,
            'activities_total' => Activity::query()->where('status', ActivityStatus::Published->value)->count(),
            'events_total' => (int) $event->total,
            'events_upcoming' => (int) $event->upcoming,
            'event_registrations' => EventRegistration::query()->count(),
            'enquiries_total' => (int) $enquiry->total,
            'enquiries_new' => (int) $enquiry->new_count,
            'blog_published' => BlogPost::query()->where('status', PostStatus::Published->value)->count(),
            'gallery_albums' => GalleryAlbum::query()->where('status', AlbumStatus::Published->value)->count(),
        ];
    }

    /**
     * Twelve-month trends and the campaign split, from the report services.
     *
     * @return array<string, mixed>
     */
    private function charts(): array
    {
        $range = DateRange::preset('last_12_months');

        return [
            'range_label' => $range->label(),
            'donation_trend' => $this->donations->trend($range),
            'donation_by_campaign' => $this->donations->byCampaign($range)->take(6)->values()->all(),
            'volunteer_trend' => $this->volunteers->trend($range),
            'enquiry_trend' => $this->contacts->trend($range),
            'event_trend' => $this->events->trend($range),
        ];
    }

    private function v(\BackedEnum $case): string
    {
        return (string) $case->value;
    }
}
