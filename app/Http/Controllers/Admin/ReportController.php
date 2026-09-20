<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityStatus;
use App\Enums\AlbumStatus;
use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Enums\EventStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PostStatus;
use App\Enums\VolunteerApplicationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\GalleryCategory;
use App\Models\VolunteerApplication;
use App\Services\Reports\ContactReportService;
use App\Services\Reports\ContentReportService;
use App\Services\Reports\DonationReportService;
use App\Services\Reports\EventReportService;
use App\Services\Reports\VolunteerReportService;
use App\Support\Reports\DateRange;
use App\Support\Reports\ReportAccess;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Report screens. Every action authorises the report's Gate ability
 * (derived from module permissions in ReportAccess) and passes only
 * aggregate data to the view.
 */
class ReportController extends Controller
{
    public function __construct(
        private DonationReportService $donations,
        private VolunteerReportService $volunteers,
        private EventReportService $events,
        private ContactReportService $contacts,
        private ContentReportService $content,
    ) {}

    public function index(): View
    {
        Gate::authorize('view-any-reports');

        return view('admin.reports.index', [
            'allowed' => ReportAccess::allowedFor(request()->user()),
        ]);
    }

    public function show(ReportFilterRequest $request, string $report): View
    {
        abort_unless(array_key_exists($report, ReportAccess::REPORTS), 404);
        Gate::authorize(ReportAccess::REPORTS[$report]);

        $range = $request->range($report === 'donations' ? 'this_month' : 'last_12_months');
        $filters = $request->filters();

        return view("admin.reports.{$report}", [
            'report' => $report,
            'range' => $range,
            'filters' => $filters,
            'presets' => DateRange::PRESETS,
            'data' => $this->data($report, $range, $filters),
            'options' => $this->filterOptions($report),
            'allowed' => ReportAccess::allowedFor($request->user()),
        ]);
    }

    /**
     * Aggregate-only CSV of the report currently on screen. Personal-data
     * exports stay on the module screens behind their own permissions.
     */
    public function export(ReportFilterRequest $request, string $report): StreamedResponse
    {
        abort_unless(array_key_exists($report, ReportAccess::REPORTS), 404);
        Gate::authorize(ReportAccess::REPORTS[$report]);

        $range = $request->range($report === 'donations' ? 'this_month' : 'last_12_months');
        $filters = $request->filters();

        $rows = match ($report) {
            'donations' => $this->donations->exportRows($range, $filters),
            'volunteers' => $this->volunteers->exportRows($range, $filters),
            'events' => $this->events->exportRows($range, $filters),
            'contacts' => $this->contacts->exportRows($range, $filters),
            default => $this->content->exportRows($report, $range, $filters),
        };

        $headings = $report === 'donations'
            ? ['Section', 'Item', 'Donations', 'Amount']
            : ['Section', 'Item', 'Count'];

        $filename = sprintf('%s-report-%s-to-%s.csv', $report, $range->from->format('Y-m-d'), $range->to->format('Y-m-d'));

        return response()->streamDownload(function () use ($headings, $rows, $report, $range) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads ₹ and accents correctly.
            fputcsv($out, [ucfirst($report).' report', $range->label(), 'Generated '.now()->format('d M Y H:i')]);
            fputcsv($out, $headings);
            foreach ($rows as $row) {
                fputcsv($out, $row);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8', 'X-Content-Type-Options' => 'nosniff']);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array<string, mixed>
     */
    private function data(string $report, DateRange $range, array $filters): array
    {
        return match ($report) {
            'donations' => $this->donations->report($range, $filters),
            'volunteers' => $this->volunteers->report($range, $filters),
            'events' => $this->events->report($range, $filters),
            'contacts' => $this->contacts->report($range, $filters),
            'activities' => $this->content->activities($range, $filters),
            'blog' => $this->content->blog($range, $filters),
            'gallery' => $this->content->gallery($range, $filters),
        };
    }

    /**
     * Select options for each report's filters.
     *
     * @return array<string, array<string, string>>
     */
    private function filterOptions(string $report): array
    {
        return match ($report) {
            'donations' => [
                'campaign' => DonationCampaign::query()->orderBy('name')->pluck('name', 'id')->all(),
                'status' => PaymentStatus::options(),
                'method' => PaymentMethod::options(),
            ],
            'volunteers' => [
                'status' => VolunteerApplicationStatus::options(),
                'state' => VolunteerApplication::query()->whereNotNull('state')->where('state', '!=', '')->distinct()->orderBy('state')->pluck('state', 'state')->all(),
            ],
            'events' => [
                'category' => EventCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
                'event' => Event::query()->orderByDesc('start_date')->limit(100)->pluck('title', 'id')->all(),
                'status' => EventStatus::options(),
            ],
            'contacts' => [
                'category' => EnquiryCategory::options(),
                'status' => EnquiryStatus::options(),
            ],
            'activities' => [
                'category' => ActivityCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
                'status' => ActivityStatus::options(),
            ],
            'blog' => [
                'category' => BlogCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
                'status' => PostStatus::options(),
            ],
            'gallery' => [
                'category' => GalleryCategory::query()->orderBy('name')->pluck('name', 'id')->all(),
                'status' => AlbumStatus::options(),
            ],
        };
    }
}
