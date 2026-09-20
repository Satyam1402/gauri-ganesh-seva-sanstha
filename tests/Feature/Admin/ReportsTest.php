<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Activity;
use App\Models\ActivityCategory;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactEnquiry;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\GalleryAlbum;
use App\Models\GalleryCategory;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Services\Reports\DashboardReportService;
use App\Services\Reports\DonationReportService;
use App\Support\Reports\DateRange;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    private const REPORTS = ['donations', 'volunteers', 'events', 'contacts', 'activities', 'blog', 'gallery'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(RoleEnum $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user;
    }

    private function campaign(string $name = 'Medical Assistance'): DonationCampaign
    {
        return DonationCampaign::create([
            'name' => $name,
            'short_description' => 'Short.',
            'full_description' => 'Full.',
            'goal_amount' => 100000,
            'status' => 'active',
        ]);
    }

    private function donation(array $overrides = []): Donation
    {
        return Donation::create(array_merge([
            'donor_name' => 'Suresh Patil',
            'donor_email' => 'suresh@example.com',
            'donor_phone' => '9876543210',
            'pan_number' => 'ABCDE1234F',
            'amount' => 1000,
            'currency' => 'INR',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'completed',
            'donated_at' => now(),
        ], $overrides));
    }

    private function volunteer(array $overrides = []): VolunteerApplication
    {
        return VolunteerApplication::create(array_merge([
            'first_name' => 'Asha',
            'last_name' => 'Patil',
            'gender' => 'female',
            'date_of_birth' => '1995-05-10',
            'email' => uniqid().'@example.com',
            'phone' => '9876543210',
            'address' => '12 Seva Marg',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pin_code' => '411001',
            'occupation' => 'Teacher',
            'skills' => 'Teaching',
            'areas_of_interest' => ['education_teaching'],
            'availability' => 'weekends',
            'emergency_contact_name' => 'Ravi Patil',
            'emergency_contact_phone' => '9123456780',
            'preferred_communication_method' => 'email',
            'consented_at' => now(),
            'status' => 'pending',
        ], $overrides));
    }

    private function enquiry(array $overrides = []): ContactEnquiry
    {
        return ContactEnquiry::create(array_merge([
            'name' => 'Asha Patil',
            'email' => uniqid().'@example.com',
            'phone' => '9876543210',
            'subject' => 'Question about food drives',
            'category' => 'general',
            'message' => 'Which areas do your food drives cover?',
            'consented_at' => now(),
            'status' => 'new',
        ], $overrides));
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'event_category_id' => EventCategory::create(['name' => 'Medical Camp', 'is_active' => true])->id,
            'title' => 'Health Camp '.uniqid(),
            'short_description' => 'Camp.',
            'full_description' => 'Details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'requires_registration' => true,
            'status' => 'published',
        ], $overrides));
    }

    /* ------------------------------------------------------------------ */
    /* Access */
    /* ------------------------------------------------------------------ */

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.reports.show', 'donations'))->assertRedirect(route('login'));
    }

    public function test_super_admin_and_admin_can_open_every_report(): void
    {
        foreach ([RoleEnum::SuperAdmin, RoleEnum::Admin] as $role) {
            $user = $this->userWithRole($role);

            $this->actingAs($user)->get(route('admin.reports.index'))->assertOk();

            foreach (self::REPORTS as $report) {
                $this->actingAs($user)->get(route('admin.reports.show', $report))->assertOk();
            }

            auth()->logout();
        }
    }

    public function test_donation_manager_sees_only_donation_reports(): void
    {
        $manager = $this->userWithRole(RoleEnum::DonationManager);

        $this->actingAs($manager)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(route('admin.reports.show', 'donations'))
            ->assertDontSee(route('admin.reports.show', 'volunteers'));

        $this->actingAs($manager)->get(route('admin.reports.show', 'donations'))->assertOk();

        foreach (array_diff(self::REPORTS, ['donations']) as $report) {
            $this->actingAs($manager)->get(route('admin.reports.show', $report))->assertForbidden();
        }
    }

    public function test_volunteer_manager_sees_only_volunteer_reports(): void
    {
        $manager = $this->userWithRole(RoleEnum::VolunteerManager);

        $this->actingAs($manager)->get(route('admin.reports.show', 'volunteers'))->assertOk();

        foreach (array_diff(self::REPORTS, ['volunteers']) as $report) {
            $this->actingAs($manager)->get(route('admin.reports.show', $report))->assertForbidden();
        }
    }

    public function test_content_manager_sees_only_content_reports(): void
    {
        $manager = $this->userWithRole(RoleEnum::ContentManager);

        foreach (['activities', 'blog', 'gallery', 'events'] as $report) {
            $this->actingAs($manager)->get(route('admin.reports.show', $report))->assertOk();
        }

        foreach (['donations', 'volunteers', 'contacts'] as $report) {
            $this->actingAs($manager)->get(route('admin.reports.show', $report))->assertForbidden();
        }
    }

    public function test_viewer_without_permissions_is_forbidden_from_reports(): void
    {
        $viewer = $this->userWithRole(RoleEnum::Viewer);

        $this->actingAs($viewer)->get(route('admin.reports.index'))->assertForbidden();

        foreach (self::REPORTS as $report) {
            $this->actingAs($viewer)->get(route('admin.reports.show', $report))->assertForbidden();
            $this->actingAs($viewer)->get(route('admin.reports.export', $report))->assertForbidden();
        }
    }

    public function test_unknown_report_returns_404(): void
    {
        $this->actingAs($this->userWithRole(RoleEnum::Admin))
            ->get(route('admin.reports.show', 'salaries'))
            ->assertNotFound();
    }

    /* ------------------------------------------------------------------ */
    /* Dashboard */
    /* ------------------------------------------------------------------ */

    public function test_dashboard_shows_all_twelve_kpis_for_admin(): void
    {
        $campaign = $this->campaign();
        $this->donation(['donation_campaign_id' => $campaign->id, 'amount' => 2500]);
        $this->donation(['amount' => 500, 'payment_status' => 'pending']);
        $this->volunteer();
        $this->volunteer(['status' => 'approved']);
        $this->enquiry();
        $this->event();
        BlogPost::create([
            'blog_category_id' => BlogCategory::create(['name' => 'Stories', 'is_active' => true])->id,
            'user_id' => $this->userWithRole(RoleEnum::Admin)->id,
            'title' => 'A Story',
            'excerpt' => 'x',
            'content' => 'y',
            'reading_minutes' => 1,
            'status' => 'published',
            'published_at' => now(),
        ]);
        GalleryAlbum::create([
            'gallery_category_id' => GalleryCategory::create(['name' => 'Festivals', 'is_active' => true])->id,
            'title' => 'Album',
            'status' => 'published',
        ]);
        Activity::create([
            'activity_category_id' => ActivityCategory::create(['name' => 'Health', 'is_active' => true])->id,
            'title' => 'Camp',
            'short_description' => 's',
            'full_description' => 'f',
            'activity_date' => now(),
            'status' => 'published',
        ]);

        $response = $this->actingAs($this->userWithRole(RoleEnum::Admin))->get(route('admin.dashboard'));

        $response->assertOk();
        foreach ([
            'Total Donations', 'Donations This Month', 'Donations This Year', 'Active Campaigns',
            'Total Volunteers', 'Pending Applications', 'Published Activities', 'Upcoming Events',
            'Event Registrations', 'New Enquiries', 'Published Blog Posts', 'Gallery Albums',
            'Donation Trend', 'By Campaign', 'Volunteer Applications', 'Contact Enquiries', 'Event Activity',
        ] as $label) {
            $response->assertSee($label);
        }

        $response->assertSee('2,500');       // completed only — pending 500 excluded
        $response->assertDontSee('Suresh Patil');
        $response->assertDontSee('ABCDE1234F');
    }

    public function test_dashboard_only_shows_kpis_the_user_may_see(): void
    {
        $this->donation();
        $this->volunteer();

        $response = $this->actingAs($this->userWithRole(RoleEnum::VolunteerManager))->get(route('admin.dashboard'));

        $response->assertOk()
            ->assertSee('Total Volunteers')
            ->assertSee('Pending Applications')
            ->assertDontSee('Total Donations')
            ->assertDontSee('New Enquiries')
            ->assertDontSee('Donation Trend');
    }

    public function test_dashboard_for_viewer_shows_empty_state_instead_of_figures(): void
    {
        $this->donation();

        $this->actingAs($this->userWithRole(RoleEnum::Viewer))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('No reports available for your role')
            ->assertDontSee('Total Donations');
    }

    public function test_dashboard_overview_is_cached_and_can_be_refreshed(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
        $this->assertTrue(Cache::has(DashboardReportService::CACHE_KEY));

        $this->donation(['amount' => 7777]);

        $this->actingAs($admin)->get(route('admin.dashboard'))->assertDontSee('7,777');
        $this->actingAs($admin)->get(route('admin.dashboard', ['fresh' => 1]))->assertSee('7,777');
    }

    public function test_dashboard_with_no_data_renders_zero_figures(): void
    {
        $this->actingAs($this->userWithRole(RoleEnum::Admin))->get(route('admin.dashboard'))
            ->assertOk()
            ->assertSee('Total Donations')
            ->assertSee('No data for this period');
    }

    /* ------------------------------------------------------------------ */
    /* Donation calculations */
    /* ------------------------------------------------------------------ */

    public function test_donation_summary_only_counts_completed_amounts(): void
    {
        $this->donation(['amount' => 1000, 'payment_method' => 'bank_transfer']);
        $this->donation(['amount' => 2000, 'payment_method' => 'razorpay']);
        $this->donation(['amount' => 5000, 'payment_status' => 'pending']);
        $this->donation(['amount' => 9000, 'payment_status' => 'failed']);
        $this->donation(['amount' => 300, 'payment_status' => 'refunded']);

        $summary = app(DonationReportService::class)->summary(DateRange::fromRequest(['period' => 'all'], 'all'));

        $this->assertSame(5, $summary['donations']);
        $this->assertSame(3000.0, $summary['total_amount']);
        $this->assertSame(2, $summary['completed_count']);
        $this->assertSame(1, $summary['pending_count']);
        $this->assertSame(5000.0, $summary['pending_amount']);
        $this->assertSame(1, $summary['failed_count']);
        $this->assertSame(1, $summary['refunded_count']);
        $this->assertSame(1000.0, $summary['offline_amount']);
        $this->assertSame(2000.0, $summary['online_amount']);
        $this->assertSame(1500.0, $summary['average_amount']);
    }

    public function test_donations_are_grouped_by_campaign_with_general_fund_for_unassigned(): void
    {
        $medical = $this->campaign('Medical Assistance');
        $education = $this->campaign('Education Support');
        $this->donation(['donation_campaign_id' => $medical->id, 'amount' => 1000]);
        $this->donation(['donation_campaign_id' => $medical->id, 'amount' => 1500]);
        $this->donation(['donation_campaign_id' => $education->id, 'amount' => 700]);
        $this->donation(['amount' => 200]);

        $rows = app(DonationReportService::class)
            ->byCampaign(DateRange::fromRequest(['period' => 'all'], 'all'))
            ->keyBy('campaign');

        $this->assertSame(2500.0, $rows['Medical Assistance']['amount']);
        $this->assertSame(2, $rows['Medical Assistance']['count']);
        $this->assertSame(700.0, $rows['Education Support']['amount']);
        $this->assertSame(200.0, $rows['General fund']['amount']);
    }

    public function test_campaign_filter_limits_the_donation_report(): void
    {
        $medical = $this->campaign('Medical Assistance');
        $education = $this->campaign('Education Support');
        $this->donation(['donation_campaign_id' => $medical->id, 'amount' => 1234]);
        $this->donation(['donation_campaign_id' => $education->id, 'amount' => 4321]);

        $response = $this->actingAs($this->userWithRole(RoleEnum::Admin))
            ->get(route('admin.reports.show', ['report' => 'donations', 'period' => 'all', 'campaign' => $medical->id]));

        $response->assertOk()->assertSee('1,234')->assertDontSee('4,321');
    }

    public function test_status_and_method_filters_limit_the_donation_report(): void
    {
        $this->donation(['amount' => 1111, 'payment_method' => 'razorpay']);
        $this->donation(['amount' => 2222, 'payment_method' => 'upi']);

        $service = app(DonationReportService::class);
        $range = DateRange::fromRequest(['period' => 'all'], 'all');

        $this->assertSame(1111.0, $service->summary($range, ['method' => 'razorpay'])['total_amount']);
        $this->assertSame(2222.0, $service->summary($range, ['method' => 'upi'])['total_amount']);
        $this->assertSame(0.0, $service->summary($range, ['status' => 'failed'])['total_amount']);
    }

    /* ------------------------------------------------------------------ */
    /* Date filtering */
    /* ------------------------------------------------------------------ */

    public function test_date_presets_select_the_expected_donations(): void
    {
        $this->donation(['amount' => 10, 'donated_at' => now()]);
        $this->donation(['amount' => 20, 'donated_at' => now()->subDay()]);
        $this->donation(['amount' => 40, 'donated_at' => now()->startOfMonth()->subDay()]);
        $this->donation(['amount' => 80, 'donated_at' => now()->startOfYear()->subDay()]);

        $service = app(DonationReportService::class);
        $total = fn (string $period) => $service->summary(DateRange::fromRequest(['period' => $period], 'all'))['total_amount'];

        $this->assertSame(10.0, $total('today'));
        $this->assertSame(20.0, $total('yesterday'));
        $this->assertSame(150.0, $total('all'));

        // "This month" includes the two donations dated inside the current month.
        $this->assertSame(30.0, $total('this_month') + (now()->day === 1 ? 20.0 : 0.0));

        // Everything before this year is excluded from "this year".
        $this->assertLessThan(150.0, $total('this_year'));
        $this->assertGreaterThanOrEqual(10.0, $total('this_year'));
    }

    public function test_custom_range_filters_and_reversed_dates_are_swapped(): void
    {
        $this->donation(['amount' => 100, 'donated_at' => '2025-03-15 10:00:00']);
        $this->donation(['amount' => 200, 'donated_at' => '2025-06-15 10:00:00']);

        $service = app(DonationReportService::class);

        $range = DateRange::fromRequest(['period' => 'custom', 'from' => '2025-03-01', 'to' => '2025-03-31'], 'all');
        $this->assertSame(100.0, $service->summary($range)['total_amount']);

        $reversed = DateRange::fromRequest(['period' => 'custom', 'from' => '2025-06-30', 'to' => '2025-03-01'], 'all');
        $this->assertSame(300.0, $service->summary($reversed)['total_amount']);
    }

    public function test_large_custom_ranges_bucket_by_year_and_are_clamped(): void
    {
        $range = DateRange::fromRequest(['period' => 'custom', 'from' => '2000-01-01', 'to' => '2030-12-31'], 'all');

        $this->assertSame('year', $range->granularity());
        $this->assertLessThanOrEqual(DateRange::MAX_CUSTOM_DAYS + 1, $range->from->diffInDays($range->to));
        $this->assertSame('2030', $range->to->format('Y'));

        $this->donation(['amount' => 42, 'donated_at' => '2029-02-10 09:00:00']);

        $trend = app(DonationReportService::class)->trend($range);

        $this->assertSame(42.0, (float) $trend['2029']);
        $this->assertLessThanOrEqual(6, count($trend));
    }

    public function test_medium_ranges_bucket_by_month_and_all_time_trims_empty_years(): void
    {
        $this->assertSame('month', DateRange::fromRequest(['period' => 'custom', 'from' => '2025-01-01', 'to' => '2025-12-31'], 'all')->granularity());

        $this->donation(['amount' => 9, 'donated_at' => '2024-05-01 09:00:00']);
        $this->donation(['amount' => 1, 'donated_at' => now()]);

        $trend = app(DonationReportService::class)->trend(DateRange::fromRequest(['period' => 'all'], 'all'));

        $this->assertSame('2024', (string) array_key_first($trend));
        $this->assertSame(now()->format('Y'), (string) array_key_last($trend));
    }

    public function test_short_ranges_bucket_by_day(): void
    {
        $this->donation(['amount' => 5, 'donated_at' => now()->startOfWeek()->addHours(9)]);

        $trend = app(DonationReportService::class)->trend(DateRange::fromRequest(['period' => 'this_week'], 'all'));

        $this->assertCount(7, $trend);
        $this->assertSame(5.0, (float) $trend[now()->startOfWeek()->format('d M')]);
    }

    public function test_invalid_filters_are_rejected(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);

        $this->actingAs($admin)->from(route('admin.reports.index'))
            ->get(route('admin.reports.show', ['report' => 'donations', 'period' => 'forever']))
            ->assertRedirect(route('admin.reports.index'))
            ->assertSessionHasErrors('period');

        $this->actingAs($admin)->from(route('admin.reports.index'))
            ->get(route('admin.reports.show', ['report' => 'donations', 'period' => 'custom']))
            ->assertSessionHasErrors(['from', 'to']);

        $this->actingAs($admin)->from(route('admin.reports.index'))
            ->get(route('admin.reports.show', ['report' => 'donations', 'campaign' => 999]))
            ->assertSessionHasErrors('campaign');
    }

    /* ------------------------------------------------------------------ */
    /* Other reports */
    /* ------------------------------------------------------------------ */

    public function test_volunteer_report_counts_by_status_and_state(): void
    {
        $this->volunteer();
        $this->volunteer(['status' => 'approved']);
        $this->volunteer(['status' => 'approved', 'state' => 'Gujarat', 'city' => 'Surat']);
        $this->volunteer(['status' => 'rejected']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::VolunteerManager))
            ->get(route('admin.reports.show', ['report' => 'volunteers', 'period' => 'all']));

        $response->assertOk()
            ->assertSee('Maharashtra')
            ->assertSee('Gujarat')
            ->assertSee('Education')
            ->assertDontSee('Asha Patil')
            ->assertDontSee('9876543210')
            ->assertDontSee('Ravi Patil');

        $this->assertSame(4, $response->viewData('data')['summary']['total']);
        $this->assertSame(2, $response->viewData('data')['summary']['approved']);
        $this->assertSame(1, $response->viewData('data')['summary']['pending']);
    }

    public function test_event_report_counts_events_and_registrations(): void
    {
        $event = $this->event();
        $event->registrations()->create(['name' => 'Asha Patil', 'email' => 'asha@example.com', 'phone' => '9876543210', 'city' => 'Pune', 'status' => 'confirmed']);
        $event->registrations()->create(['name' => 'Ravi Patil', 'email' => 'ravi@example.com', 'phone' => '9876543211', 'city' => 'Pune', 'status' => 'cancelled']);
        $this->event(['start_date' => now()->subDays(30)->toDateString(), 'status' => 'published']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::ContentManager))
            ->get(route('admin.reports.show', ['report' => 'events', 'period' => 'all']));

        $response->assertOk()->assertDontSee('asha@example.com')->assertDontSee('Ravi Patil');

        $data = $response->viewData('data');
        $this->assertSame(2, $data['summary']['total']);
        $this->assertSame(1, $data['summary']['upcoming']);
        $this->assertSame(2, $data['summary']['registrations']);
        $this->assertSame(1, $data['summary']['registrations_confirmed']);
        $this->assertSame(1, $data['summary']['registrations_cancelled']);
    }

    public function test_event_filter_limits_the_event_report(): void
    {
        $first = $this->event(['title' => 'First Camp']);
        $first->registrations()->create(['name' => 'A', 'email' => 'a@example.com', 'phone' => '9876543210', 'city' => 'Pune', 'status' => 'confirmed']);
        $second = $this->event(['title' => 'Second Camp']);
        $second->registrations()->create(['name' => 'B', 'email' => 'b@example.com', 'phone' => '9876543210', 'city' => 'Pune', 'status' => 'confirmed']);
        $second->registrations()->create(['name' => 'C', 'email' => 'c@example.com', 'phone' => '9876543210', 'city' => 'Pune', 'status' => 'confirmed']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::Admin))
            ->get(route('admin.reports.show', ['report' => 'events', 'period' => 'all', 'event' => $second->id]));

        $data = $response->assertOk()->viewData('data');
        $this->assertSame(1, $data['summary']['total']);
        $this->assertSame(2, $data['summary']['registrations']);
    }

    public function test_contact_report_counts_status_category_and_response_time(): void
    {
        $this->enquiry();
        $this->enquiry(['status' => 'resolved', 'category' => 'volunteer', 'replied_at' => now()->addHours(4), 'created_at' => now()]);
        $this->enquiry(['status' => 'spam']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::Admin))
            ->get(route('admin.reports.show', ['report' => 'contacts', 'period' => 'all']));

        $response->assertOk()->assertDontSee('Asha Patil')->assertDontSee('food drives cover');

        $data = $response->viewData('data');
        $this->assertSame(3, $data['summary']['total']);
        $this->assertSame(1, $data['summary']['new']);
        $this->assertSame(1, $data['summary']['resolved']);
        $this->assertSame(1, $data['summary']['spam']);
        $this->assertEqualsWithDelta(4.0, $data['responseTime'], 0.05);
    }

    public function test_blog_report_shows_publishing_stats_and_real_view_counts(): void
    {
        $category = BlogCategory::create(['name' => 'Stories', 'is_active' => true]);
        $author = $this->userWithRole(RoleEnum::Admin);
        $base = ['blog_category_id' => $category->id, 'user_id' => $author->id, 'excerpt' => 'x', 'content' => 'y', 'reading_minutes' => 1];

        BlogPost::create($base + ['title' => 'Popular Post', 'status' => 'published', 'published_at' => now(), 'views_count' => 57]);
        BlogPost::create($base + ['title' => 'Quiet Post', 'status' => 'published', 'published_at' => now()->subDay(), 'views_count' => 3]);
        BlogPost::create($base + ['title' => 'Draft Post', 'status' => 'draft']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::ContentManager))
            ->get(route('admin.reports.show', ['report' => 'blog', 'period' => 'all']));

        $response->assertOk()->assertSee('Popular Post')->assertSee('57 views');

        $data = $response->viewData('data');
        $this->assertSame(3, $data['summary']['total']);
        $this->assertSame(2, $data['summary']['published']);
        $this->assertSame(1, $data['summary']['draft']);
        $this->assertSame(60, $data['summary']['views']);
    }

    public function test_activity_and_gallery_reports_render_with_category_breakdowns(): void
    {
        $activityCategory = ActivityCategory::create(['name' => 'Health Camps', 'is_active' => true]);
        Activity::create(['activity_category_id' => $activityCategory->id, 'title' => 'Camp', 'short_description' => 's', 'full_description' => 'f', 'activity_date' => now(), 'status' => 'published']);
        $galleryCategory = GalleryCategory::create(['name' => 'Festivals', 'is_active' => true]);
        GalleryAlbum::create(['gallery_category_id' => $galleryCategory->id, 'title' => 'Ganesh Chaturthi', 'status' => 'published']);

        $manager = $this->userWithRole(RoleEnum::ContentManager);

        $this->actingAs($manager)->get(route('admin.reports.show', ['report' => 'activities', 'period' => 'all']))
            ->assertOk()->assertSee('Health Camps')->assertSee('Camp');

        $this->actingAs($manager)->get(route('admin.reports.show', ['report' => 'gallery', 'period' => 'all']))
            ->assertOk()->assertSee('Festivals')->assertSee('Ganesh Chaturthi');
    }

    public function test_every_report_renders_with_an_empty_database(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);

        foreach (self::REPORTS as $report) {
            $this->actingAs($admin)
                ->get(route('admin.reports.show', ['report' => $report, 'period' => 'all']))
                ->assertOk();
        }
    }

    /* ------------------------------------------------------------------ */
    /* Exports */
    /* ------------------------------------------------------------------ */

    public function test_donation_export_contains_aggregates_but_no_donor_details(): void
    {
        $campaign = $this->campaign();
        $this->donation(['donation_campaign_id' => $campaign->id, 'amount' => 1500]);
        $this->donation(['donation_campaign_id' => $campaign->id, 'amount' => 500, 'payment_method' => 'razorpay']);

        $response = $this->actingAs($this->userWithRole(RoleEnum::DonationManager))
            ->get(route('admin.reports.export', ['report' => 'donations', 'period' => 'all']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('donations-report-', $response->headers->get('Content-Disposition'));
        $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));

        $csv = $response->streamedContent();

        $this->assertStringContainsString('Medical Assistance', $csv);
        $this->assertStringContainsString('2000', $csv);
        $this->assertStringNotContainsString('Suresh Patil', $csv);
        $this->assertStringNotContainsString('suresh@example.com', $csv);
        $this->assertStringNotContainsString('9876543210', $csv);
        $this->assertStringNotContainsString('ABCDE1234F', $csv);
    }

    public function test_exports_follow_the_same_permissions_as_reports(): void
    {
        $this->volunteer();

        $donationManager = $this->userWithRole(RoleEnum::DonationManager);
        $this->actingAs($donationManager)->get(route('admin.reports.export', 'volunteers'))->assertForbidden();
        $this->actingAs($donationManager)->get(route('admin.reports.export', 'donations'))->assertOk();

        auth()->logout();

        $volunteerManager = $this->userWithRole(RoleEnum::VolunteerManager);
        $this->actingAs($volunteerManager)->get(route('admin.reports.export', 'donations'))->assertForbidden();

        $csv = $this->actingAs($volunteerManager)
            ->get(route('admin.reports.export', ['report' => 'volunteers', 'period' => 'all']))
            ->assertOk()
            ->streamedContent();

        $this->assertStringContainsString('Maharashtra', $csv);
        $this->assertStringNotContainsString('Asha', $csv);
        $this->assertStringNotContainsString('@example.com', $csv);
    }

    public function test_every_report_exports_on_an_empty_database(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);

        foreach (self::REPORTS as $report) {
            $csv = $this->actingAs($admin)
                ->get(route('admin.reports.export', ['report' => $report, 'period' => 'this_month']))
                ->assertOk()
                ->streamedContent();

            $this->assertStringContainsString(ucfirst($report).' report', $csv);
        }
    }

    /* ------------------------------------------------------------------ */
    /* Performance */
    /* ------------------------------------------------------------------ */

    public function test_donation_report_query_count_does_not_grow_with_data(): void
    {
        $campaign = $this->campaign();
        for ($i = 0; $i < 30; $i++) {
            $this->donation(['donation_campaign_id' => $i % 2 ? $campaign->id : null, 'amount' => 100 + $i, 'donated_at' => now()->subDays($i * 10)]);
        }

        $admin = $this->userWithRole(RoleEnum::Admin);
        $this->actingAs($admin)->get(route('admin.reports.show', ['report' => 'donations', 'period' => 'this_year']))->assertOk();

        DB::enableQueryLog();
        $this->actingAs($admin)->get(route('admin.reports.show', ['report' => 'donations', 'period' => 'last_12_months']))->assertOk();
        $queries = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThan(20, $queries, "Donation report ran {$queries} queries.");
    }
}
