<?php

namespace Tests\Feature;

use App\Enums\PaymentStatus;
use App\Enums\Role as RoleEnum;
use App\Enums\VolunteerApplicationStatus;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\ContactEnquiry;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\User;
use App\Models\VolunteerApplication;
use App\Notifications\Blog\CommentAwaitingModerationAlert;
use App\Notifications\Contact\EnquiryAcknowledgement;
use App\Notifications\Contact\EnquiryReply;
use App\Notifications\Contact\NewEnquiryAlert;
use App\Notifications\Donations\DonationConfirmation;
use App\Notifications\Donations\DonationFailed;
use App\Notifications\Donations\NewDonationAlert;
use App\Notifications\Events\EventRegistrationConfirmation;
use App\Notifications\Events\NewEventRegistrationAlert;
use App\Notifications\System\SystemAlert;
use App\Notifications\Volunteers\NewVolunteerApplicationAlert;
use App\Notifications\Volunteers\VolunteerApplicationReceived;
use App\Notifications\Volunteers\VolunteerApplicationStatusUpdated;
use App\Services\BlogCommentService;
use App\Services\ContactEnquiryService;
use App\Services\DonationService;
use App\Services\EventRegistrationService;
use App\Services\SettingsService;
use App\Services\VolunteerApplicationService;
use App\Support\Notifications\ChannelResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Contracts\Mail\Factory as MailFactory;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use RuntimeException;
use Tests\TestCase;

class NotificationDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    // ── fixtures ──────────────────────────────────────────────────────────

    private function userWithRole(RoleEnum $role, array $overrides = []): User
    {
        $user = User::factory()->create($overrides);
        $user->assignRole($role->value);

        return $user;
    }

    private function campaign(): DonationCampaign
    {
        return DonationCampaign::create([
            'name' => 'Medical Assistance',
            'short_description' => 'Free health camps and medicines.',
            'full_description' => 'Full description.',
            'goal_amount' => 750000,
            'status' => 'active',
        ]);
    }

    private function donation(array $overrides = []): Donation
    {
        return Donation::create(array_merge([
            'donation_campaign_id' => $this->campaign()->id,
            'donor_name' => 'Suresh Patil',
            'donor_email' => 'suresh@example.com',
            'donor_phone' => '9822001122',
            'pan_number' => 'ABCDE1234F',
            'amount' => 2100,
            'currency' => 'INR',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'donated_at' => now(),
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function applicationPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Asha',
            'last_name' => 'Patil',
            'gender' => 'female',
            'date_of_birth' => '1995-05-10',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'address' => '12 Seva Marg',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pin_code' => '411001',
            'occupation' => 'Teacher',
            'skills' => 'Teaching, first aid',
            'areas_of_interest' => ['education_teaching'],
            'availability' => 'weekends',
            'emergency_contact_name' => 'Ravi Patil',
            'emergency_contact_phone' => '9123456780',
            'preferred_communication_method' => 'email',
        ], $overrides);
    }

    private function application(array $overrides = []): VolunteerApplication
    {
        return VolunteerApplication::create($this->applicationPayload(array_merge([
            'consented_at' => now(),
            'status' => 'pending',
            'admin_notes' => 'INTERNAL-NOTE-DO-NOT-LEAK',
        ], $overrides)));
    }

    private function enquiry(array $overrides = []): ContactEnquiry
    {
        return ContactEnquiry::create(array_merge([
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'subject' => 'Question about weekend food drives',
            'category' => 'general',
            'message' => 'Which areas do your weekend food drives currently cover?',
            'status' => 'new',
            'consented_at' => now(),
            'admin_notes' => 'INTERNAL-NOTE-DO-NOT-LEAK',
        ], $overrides));
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'event_category_id' => EventCategory::create(['name' => 'Medical Camp', 'is_active' => true])->id,
            'title' => 'Free Health Camp',
            'short_description' => 'Free checkups for everyone.',
            'full_description' => 'Full details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'start_time' => '10:00',
            'end_time' => '13:00',
            'venue' => 'Community Hall',
            'city' => 'Pune',
            'requires_registration' => true,
            'status' => 'published',
        ], $overrides));
    }

    private function blogPost(): BlogPost
    {
        return BlogPost::create([
            'blog_category_id' => BlogCategory::create(['name' => 'Success Stories', 'is_active' => true])->id,
            'user_id' => User::factory()->create()->id,
            'title' => 'A Story of Change',
            'excerpt' => 'How one scholarship changed a life.',
            'content' => 'It began with a single sponsor.',
            'published_at' => now()->subDay(),
            'reading_minutes' => 1,
            'allow_comments' => true,
            'status' => 'published',
        ]);
    }

    // ── donations ─────────────────────────────────────────────────────────

    public function test_completing_a_donation_notifies_the_donor_and_authorised_admins_only(): void
    {
        Notification::fake();

        $donationManager = $this->userWithRole(RoleEnum::DonationManager);
        $superAdmin = $this->userWithRole(RoleEnum::SuperAdmin);
        $contentManager = $this->userWithRole(RoleEnum::ContentManager);
        $suspended = $this->userWithRole(RoleEnum::DonationManager, ['status' => 'suspended']);

        $donation = $this->donation();

        app(DonationService::class)->markCompleted($donation, 'UTR123');

        Notification::assertSentTo($donation, DonationConfirmation::class, function (DonationConfirmation $notification, array $channels) {
            return $channels === ['mail'] && $notification->donation->receipt_number !== null;
        });

        Notification::assertSentTo($donationManager, NewDonationAlert::class, fn ($n, array $channels) => $channels === ['database', 'mail']);
        Notification::assertSentTo($superAdmin, NewDonationAlert::class);
        Notification::assertNotSentTo($contentManager, NewDonationAlert::class);
        Notification::assertNotSentTo($suspended, NewDonationAlert::class);
    }

    public function test_the_admin_alert_is_not_sent_to_the_donor_and_carries_no_pii_in_its_payload(): void
    {
        $donationManager = $this->userWithRole(RoleEnum::DonationManager)->fresh();
        $donation = $this->donation(['payment_status' => 'completed', 'receipt_number' => 'GGSS-2026-000001']);

        Notification::sendNow($donationManager, new NewDonationAlert($donation), ['database']);

        $row = $donationManager->notifications()->firstOrFail();

        $this->assertSame(NewDonationAlert::class, $row->type);
        $this->assertSame('donation', $row->data['category']);
        $this->assertStringContainsString('Suresh Patil', $row->data['title']);
        $this->assertSame(route('admin.donations.show', $donation), $row->data['url']);
        $this->assertStringNotContainsString('ABCDE1234F', json_encode($row->data));
        $this->assertStringNotContainsString('suresh@example.com', json_encode($row->data));
        $this->assertStringNotContainsString('9822001122', json_encode($row->data));
        $this->assertSame(0, $donation->notifications()->count());
    }

    public function test_a_confirmed_failure_notifies_the_donor_without_claiming_success(): void
    {
        Notification::fake();

        $donation = $this->donation(['payment_method' => 'razorpay']);

        app(DonationService::class)->markFailed($donation, 'Gateway callback reported failure.');

        Notification::assertSentTo($donation, DonationFailed::class);
        Notification::assertNotSentTo($donation, DonationConfirmation::class);
        $this->assertSame(PaymentStatus::Failed, $donation->fresh()->payment_status);
        $this->assertNull($donation->fresh()->receipt_number);
    }

    public function test_admins_are_alerted_for_pending_offline_donations_but_not_abandoned_online_checkouts(): void
    {
        Notification::fake();

        $manager = $this->userWithRole(RoleEnum::DonationManager);
        $service = app(DonationService::class);

        $offline = $service->createPendingDonation([
            'donor_name' => 'Ramesh', 'donor_email' => 'ramesh@example.com', 'amount' => 500, 'payment_method' => 'bank_transfer',
        ]);
        $online = $service->createPendingDonation([
            'donor_name' => 'Ramesh', 'donor_email' => 'ramesh@example.com', 'amount' => 500, 'payment_method' => 'razorpay',
        ]);

        Notification::assertSentTo($manager, NewDonationAlert::class, fn (NewDonationAlert $n) => $n->donation->is($offline));
        Notification::assertNotSentTo($manager, NewDonationAlert::class, fn (NewDonationAlert $n) => $n->donation->is($online));
        Notification::assertNotSentTo($offline, DonationConfirmation::class);
    }

    public function test_the_donation_confirmation_email_shows_receipt_details_and_masks_the_pan(): void
    {
        $donation = $this->donation(['payment_status' => 'completed', 'receipt_number' => 'GGSS-2026-000042', 'transaction_id' => 'UTR777']);

        $html = (new DonationConfirmation($donation))->toMail($donation)->render();

        $this->assertStringContainsString('GGSS-2026-000042', $html);
        $this->assertStringContainsString('UTR777', $html);
        $this->assertStringContainsString('Medical Assistance', $html);
        $this->assertStringContainsString('2,100', $html);
        $this->assertStringContainsString('XXXXXX234F', $html);
        $this->assertStringNotContainsString('ABCDE1234F', $html);
    }

    // ── volunteers ────────────────────────────────────────────────────────

    public function test_submitting_a_volunteer_application_confirms_to_the_applicant_and_alerts_volunteer_managers(): void
    {
        Notification::fake();

        $volunteerManager = $this->userWithRole(RoleEnum::VolunteerManager);
        $donationManager = $this->userWithRole(RoleEnum::DonationManager);

        $application = app(VolunteerApplicationService::class)->submit($this->applicationPayload(['consent' => '1']));

        Notification::assertSentTo($application, VolunteerApplicationReceived::class);
        Notification::assertSentTo($volunteerManager, NewVolunteerApplicationAlert::class);
        Notification::assertNotSentTo($donationManager, NewVolunteerApplicationAlert::class);
    }

    public function test_every_reviewable_status_change_notifies_the_applicant_but_archiving_is_silent(): void
    {
        Notification::fake();
        $this->actingAs($this->userWithRole(RoleEnum::VolunteerManager));

        $service = app(VolunteerApplicationService::class);

        foreach ([VolunteerApplicationStatus::UnderReview, VolunteerApplicationStatus::OnHold, VolunteerApplicationStatus::Approved, VolunteerApplicationStatus::Rejected] as $status) {
            $application = $this->application(['email' => $status->value.'@example.com']);
            $service->changeStatus($application, $status);

            Notification::assertSentTo($application, VolunteerApplicationStatusUpdated::class, fn (VolunteerApplicationStatusUpdated $n) => $n->application->status === $status);
        }

        $archived = $this->application(['email' => 'archived@example.com']);
        $service->changeStatus($archived, VolunteerApplicationStatus::Archived);

        Notification::assertNotSentTo($archived, VolunteerApplicationStatusUpdated::class);
    }

    public function test_volunteer_emails_never_contain_internal_admin_notes(): void
    {
        $application = $this->application(['status' => 'rejected']);

        $received = (new VolunteerApplicationReceived($application))->toMail($application)->render();
        $status = (new VolunteerApplicationStatusUpdated($application))->toMail($application)->render();

        $this->assertStringContainsString($application->reference, $received);
        $this->assertStringContainsString('Rejected', $status);
        $this->assertStringNotContainsString('INTERNAL-NOTE-DO-NOT-LEAK', $received);
        $this->assertStringNotContainsString('INTERNAL-NOTE-DO-NOT-LEAK', $status);
    }

    // ── events ────────────────────────────────────────────────────────────

    public function test_registering_for_an_event_confirms_to_the_participant_and_alerts_event_staff(): void
    {
        Notification::fake();

        $contentManager = $this->userWithRole(RoleEnum::ContentManager);
        $volunteerManager = $this->userWithRole(RoleEnum::VolunteerManager);
        $event = $this->event();

        $registration = app(EventRegistrationService::class)->register($event, [
            'name' => 'Asha Patil', 'email' => 'asha@example.com', 'phone' => '9876543210', 'city' => 'Pune',
        ]);

        Notification::assertSentTo($registration, EventRegistrationConfirmation::class);
        Notification::assertSentTo($contentManager, NewEventRegistrationAlert::class);
        Notification::assertNotSentTo($volunteerManager, NewEventRegistrationAlert::class);

        $html = (new EventRegistrationConfirmation($registration))->toMail($registration)->render();

        $this->assertStringContainsString('Free Health Camp', $html);
        $this->assertStringContainsString($event->dateRange(), $html);
        $this->assertStringContainsString('10:00 AM', $html);
        $this->assertStringContainsString('Community Hall, Pune', $html);
        $this->assertStringContainsString($registration->registrationNumber(), $html);
    }

    // ── contact ───────────────────────────────────────────────────────────

    public function test_an_enquiry_is_acknowledged_and_staff_are_alerted_and_a_reply_reaches_the_sender(): void
    {
        Notification::fake();

        $admin = $this->userWithRole(RoleEnum::Admin);
        $editor = $this->userWithRole(RoleEnum::Editor);
        $service = app(ContactEnquiryService::class);

        $enquiry = $service->submit([
            'name' => 'Asha Patil', 'email' => 'asha@example.com', 'subject' => 'Hello', 'category' => 'general', 'message' => 'A question.',
        ]);

        Notification::assertSentTo($enquiry, EnquiryAcknowledgement::class);
        Notification::assertSentTo($admin, NewEnquiryAlert::class);
        Notification::assertNotSentTo($editor, NewEnquiryAlert::class);

        $reply = $service->reply($enquiry, $admin, 'Thanks for writing in — here is our answer.');

        Notification::assertSentTo($enquiry, EnquiryReply::class, fn (EnquiryReply $n) => $n->reply->is($reply));
    }

    public function test_the_reply_email_contains_the_reply_but_never_internal_notes(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);
        $enquiry = $this->enquiry();
        $reply = $enquiry->replies()->create(['user_id' => $admin->id, 'message' => 'Our weekend drives cover Hadapsar and Kothrud.']);

        $html = (new EnquiryReply($enquiry, $reply))->toMail($enquiry)->render();

        $this->assertStringContainsString('Hadapsar and Kothrud', $html);
        $this->assertStringContainsString($enquiry->reference, $html);
        $this->assertStringNotContainsString('INTERNAL-NOTE-DO-NOT-LEAK', $html);
    }

    // ── comments & system ─────────────────────────────────────────────────

    public function test_a_new_comment_creates_an_in_app_only_alert_for_blog_managers(): void
    {
        Notification::fake();

        $editor = $this->userWithRole(RoleEnum::Editor);
        $donationManager = $this->userWithRole(RoleEnum::DonationManager);

        app(BlogCommentService::class)->submit($this->blogPost(), ['name' => 'Ravi', 'email' => 'ravi@example.com', 'body' => 'Lovely story.']);

        Notification::assertSentTo($editor, CommentAwaitingModerationAlert::class, fn ($n, array $channels) => $channels === ['database']);
        Notification::assertNotSentTo($donationManager, CommentAwaitingModerationAlert::class);
    }

    public function test_the_generic_admin_alert_email_renders_its_body_through_the_real_mailer(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);
        $alert = new SystemAlert('Disk almost full', "Only 2% left on /var/www.\nPlease act.", route('admin.dashboard'));

        // Render through the Mailer (not MailMessage::render) so the mailer's
        // own $message variable is present, exactly as in production.
        $mailMessage = $alert->toMail($admin);
        $html = app('mailer')->render($mailMessage->view, $mailMessage->data());

        $this->assertStringContainsString('Disk almost full', $html);
        $this->assertStringContainsString('Only 2% left on /var/www.', $html);
        $this->assertStringContainsString(route('admin.dashboard'), $html);
        $this->assertStringContainsString('System', $html);
    }

    // ── recipients, channels & queueing ───────────────────────────────────

    public function test_a_configured_team_inbox_is_copied_on_demand_unless_it_belongs_to_a_user_recipient(): void
    {
        Notification::fake();
        config(['contact.admin_notification_email' => 'team@example.com']);

        $this->userWithRole(RoleEnum::Admin, ['email' => 'admin@example.com']);
        app(ContactEnquiryService::class)->submit(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'S', 'category' => 'general', 'message' => 'M']);

        Notification::assertSentOnDemand(NewEnquiryAlert::class, fn ($n, array $channels, AnonymousNotifiable $notifiable) => $notifiable->routes['mail'] === 'team@example.com' && $channels === ['mail']);

        // Same address as an existing recipient → no duplicate copy.
        config(['contact.admin_notification_email' => 'admin@example.com']);
        app(ContactEnquiryService::class)->submit(['name' => 'B', 'email' => 'b@example.com', 'subject' => 'S', 'category' => 'general', 'message' => 'M']);

        Notification::assertSentOnDemandTimes(NewEnquiryAlert::class, 1);
    }

    public function test_a_malformed_or_empty_team_inbox_is_ignored(): void
    {
        Notification::fake();
        config(['contact.admin_notification_email' => 'not-an-email']);

        app(ContactEnquiryService::class)->submit(['name' => 'A', 'email' => 'a@example.com', 'subject' => 'S', 'category' => 'general', 'message' => 'M']);

        Notification::assertSentOnDemandTimes(NewEnquiryAlert::class, 0);
    }

    public function test_channels_follow_the_site_wide_switches_and_public_recipients_never_get_database_rows(): void
    {
        $resolver = app(ChannelResolver::class);
        $admin = $this->userWithRole(RoleEnum::Admin);
        $donation = $this->donation();

        $this->assertSame(['database', 'mail'], $resolver->channelsFor($admin, new NewDonationAlert($donation)));
        $this->assertSame(['mail'], $resolver->channelsFor($donation, new DonationConfirmation($donation)));
        $this->assertSame(['mail'], $resolver->channelsFor(Notification::route('mail', 'x@example.com'), new NewDonationAlert($donation)));

        config(['notifications.channels.mail' => false]);

        $this->assertSame(['database'], $resolver->channelsFor($admin, new NewDonationAlert($donation)));
        $this->assertSame([], $resolver->channelsFor($donation, new DonationConfirmation($donation)));
    }

    public function test_notifications_are_queued_and_carry_retry_settings(): void
    {
        Queue::fake();
        config(['notifications.tries' => 4]);

        $donation = $this->donation();
        $notification = new DonationConfirmation($donation);

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame(4, $notification->tries);
        $this->assertSame([60, 300, 900], $notification->backoff());

        $donation->notify($notification);

        Queue::assertPushed(SendQueuedNotifications::class, fn (SendQueuedNotifications $job) => $job->notification instanceof DonationConfirmation);
    }

    public function test_a_mail_outage_is_logged_and_alerted_but_never_fails_the_business_operation(): void
    {
        Log::spy();
        $superAdmin = $this->userWithRole(RoleEnum::SuperAdmin);

        // The queue runs sync in tests, so a broken mailer surfaces immediately.
        $this->mock(MailFactory::class)->shouldReceive('mailer')->andThrow(new RuntimeException('SMTP connection refused'));

        $enquiry = app(ContactEnquiryService::class)->submit([
            'name' => 'Asha Patil', 'email' => 'asha@example.com', 'subject' => 'Hello', 'category' => 'general', 'message' => 'A question.',
        ]);

        $this->assertTrue($enquiry->exists);
        $this->assertDatabaseHas('contact_enquiries', ['id' => $enquiry->id, 'status' => 'new']);

        Log::shouldHaveReceived('error')->withArgs(fn (string $message) => str_contains($message, 'Notification'))->atLeast()->once();

        $alert = $superAdmin->notifications()->where('type', SystemAlert::class)->first();
        $this->assertNotNull($alert, 'A failed job should surface as an in-app system alert.');
        $this->assertStringContainsString('Background job failed', $alert->data['title']);
        $this->assertStringNotContainsString('SMTP connection refused', json_encode($alert->data));
    }

    // ── branding & framework mail ─────────────────────────────────────────

    public function test_email_branding_comes_from_site_settings_not_hardcoded_values(): void
    {
        $settings = app(SettingsService::class);
        $settings->set('general.site_name', 'Test Seva Trust');
        $settings->set('footer.email', 'hello@testseva.org');
        $settings->set('footer.phone', '+91 90000 00000');
        $settings->updateGroup('social', ['facebook_url' => 'https://facebook.com/testseva']);

        $enquiry = $this->enquiry();
        $message = (new EnquiryAcknowledgement($enquiry))->toMail($enquiry);
        $html = $message->render();

        $this->assertStringContainsString('Test Seva Trust', $message->subject);
        $this->assertStringContainsString('Test Seva Trust', $html);
        $this->assertStringContainsString('hello@testseva.org', $html);
        $this->assertStringContainsString('+91 90000 00000', $html);
        $this->assertStringContainsString('https://facebook.com/testseva', $html);
        $this->assertStringContainsString(url('/'), $html);
        $this->assertStringNotContainsString('Gauri Ganesh Seva Sanstha', $html);
    }

    public function test_the_password_reset_email_uses_the_branded_layout(): void
    {
        $user = User::factory()->create();

        $message = (new ResetPassword('token-123'))->toMail($user);
        $html = $message->render();

        $this->assertStringContainsString(setting('general.site_name'), $message->subject);
        $this->assertStringContainsString('Reset Your Password', $html);
        $this->assertStringContainsString('token-123', $html);
        $this->assertStringContainsString(urlencode($user->email), $html);
    }
}
