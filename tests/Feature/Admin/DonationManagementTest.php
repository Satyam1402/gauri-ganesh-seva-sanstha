<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Mail\DonationReceiptMail;
use App\Mail\DonationThankYouMail;
use App\Models\Donation;
use App\Models\DonationCampaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DonationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    private function campaign(array $overrides = []): DonationCampaign
    {
        return DonationCampaign::create(array_merge([
            'name' => 'Medical Assistance',
            'short_description' => 'Free health camps and medicines.',
            'full_description' => 'Full description.',
            'goal_amount' => 750000,
            'status' => 'active',
        ], $overrides));
    }

    private function pendingDonation(?DonationCampaign $campaign = null, array $overrides = []): Donation
    {
        return Donation::create(array_merge([
            'donation_campaign_id' => $campaign?->id,
            'donor_name' => 'Suresh Patil',
            'donor_email' => 'suresh@example.com',
            'amount' => 2100,
            'currency' => 'INR',
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
            'donated_at' => now(),
        ], $overrides));
    }

    public function test_user_without_manage_donations_permission_is_forbidden(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $this->actingAs($contentManager)->get(route('admin.donations.index'))->assertForbidden();
    }

    public function test_admin_can_record_a_completed_donation_with_receipt_and_campaign_totals(): void
    {
        Mail::fake();

        $campaign = $this->campaign();

        $response = $this->actingAs($this->admin())->post(route('admin.donations.store'), [
            'donation_campaign_id' => $campaign->id,
            'donor_name' => 'Anita Deshmukh',
            'donor_email' => 'anita@example.com',
            'donor_phone' => '9876543210',
            'pan_number' => 'ABCDE1234F',
            'amount' => 5100,
            'payment_method' => 'bank_transfer',
            'transaction_id' => 'UTR123456',
            'payment_status' => 'completed',
            'donated_at' => now()->toDateString(),
            'send_emails' => 1,
        ]);

        $donation = Donation::firstOrFail();
        $response->assertRedirect(route('admin.donations.show', $donation));

        $this->assertSame('completed', $donation->payment_status->value);
        $this->assertNotNull($donation->receipt_number);
        $this->assertStringContainsString('GGSS-', $donation->receipt_number);
        $this->assertSame(5100.0, (float) $campaign->fresh()->raised_amount);

        Mail::assertQueued(DonationReceiptMail::class, fn ($mail) => $mail->hasTo('anita@example.com'));
        Mail::assertQueued(DonationThankYouMail::class, fn ($mail) => $mail->hasTo('anita@example.com'));
    }

    public function test_verifying_a_pending_donation_completes_it_and_sends_the_receipt(): void
    {
        Mail::fake();

        $campaign = $this->campaign();
        $donation = $this->pendingDonation($campaign);

        $this->actingAs($this->admin())
            ->patch(route('admin.donations.complete', $donation), ['transaction_id' => 'UTR999'])
            ->assertRedirect();

        $donation = $donation->fresh();
        $this->assertSame('completed', $donation->payment_status->value);
        $this->assertSame('UTR999', $donation->transaction_id);
        $this->assertNotNull($donation->receipt_number);
        $this->assertSame(2100.0, (float) $campaign->fresh()->raised_amount);

        Mail::assertQueued(DonationReceiptMail::class);
    }

    public function test_marking_a_donation_failed_does_not_touch_campaign_totals(): void
    {
        $campaign = $this->campaign();
        $donation = $this->pendingDonation($campaign);

        $this->actingAs($this->admin())
            ->patch(route('admin.donations.fail', $donation))
            ->assertRedirect();

        $this->assertSame('failed', $donation->fresh()->payment_status->value);
        $this->assertSame(0.0, (float) $campaign->fresh()->raised_amount);
    }

    public function test_refunding_a_completed_donation_reduces_campaign_totals(): void
    {
        Mail::fake();

        $campaign = $this->campaign();
        $donation = $this->pendingDonation($campaign);

        $this->actingAs($this->admin())->patch(route('admin.donations.complete', $donation))->assertRedirect();
        $this->assertSame(2100.0, (float) $campaign->fresh()->raised_amount);

        $donation = $donation->fresh();

        $this->actingAs($this->admin())->put(route('admin.donations.update', $donation), [
            'donation_campaign_id' => $campaign->id,
            'donor_name' => $donation->donor_name,
            'donor_email' => $donation->donor_email,
            'amount' => 2100,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'refunded',
            'donated_at' => $donation->donated_at->toDateString(),
        ])->assertRedirect(route('admin.donations.show', $donation));

        $this->assertSame('refunded', $donation->fresh()->payment_status->value);
        $this->assertSame(0.0, (float) $campaign->fresh()->raised_amount);
    }

    public function test_donations_can_be_exported_as_csv(): void
    {
        Mail::fake();
        $this->pendingDonation($this->campaign());

        $response = $this->actingAs($this->admin())->get(route('admin.donations.export'));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Donor Name', $content);
        $this->assertStringContainsString('Suresh Patil', $content);
    }

    public function test_donations_can_be_exported_as_xlsx(): void
    {
        Mail::fake();
        $this->pendingDonation($this->campaign());

        $response = $this->actingAs($this->admin())->get(route('admin.donations.export', ['format' => 'xlsx']));

        $response->assertOk();
        $this->assertStringContainsString('spreadsheetml', $response->headers->get('Content-Type'));
    }

    public function test_reports_page_shows_revenue_summary_and_top_donors(): void
    {
        Mail::fake();

        $campaign = $this->campaign();
        $donation = $this->pendingDonation($campaign);
        $this->actingAs($this->admin())->patch(route('admin.donations.complete', $donation));

        $response = $this->actingAs($this->admin())->get(route('admin.donation-reports.index'));

        $response->assertOk();
        $response->assertSee('Total Raised');
        $response->assertSee('Suresh Patil');
        $response->assertSee('Medical Assistance');
    }

    public function test_donation_manager_can_access_reports(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleEnum::DonationManager->value);

        $this->actingAs($manager)->get(route('admin.donation-reports.index'))->assertOk();
    }
}
