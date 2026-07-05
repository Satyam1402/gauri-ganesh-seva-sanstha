<?php

namespace Tests\Feature;

use App\Mail\NewDonationNotificationMail;
use App\Models\Donation;
use App\Models\DonationCampaign;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class DonationsPageTest extends TestCase
{
    use RefreshDatabase;

    private function campaign(array $overrides = []): DonationCampaign
    {
        return DonationCampaign::create(array_merge([
            'name' => 'Food Distribution',
            'short_description' => 'Meals for families facing food insecurity.',
            'full_description' => 'Full campaign description.',
            'goal_amount' => 500000,
            'status' => 'active',
        ], $overrides));
    }

    public function test_campaign_listing_shows_only_active_campaigns(): void
    {
        $this->campaign(['name' => 'Active Campaign']);
        $this->campaign(['name' => 'Draft Campaign', 'status' => 'draft']);

        $response = $this->get(route('donations.campaigns.index'));

        $response->assertOk();
        $response->assertSee('Active Campaign');
        $response->assertDontSee('Draft Campaign');
    }

    public function test_campaign_detail_page_renders_for_active_campaigns(): void
    {
        $campaign = $this->campaign();

        $response = $this->get(route('donations.campaigns.show', $campaign));

        $response->assertOk();
        $response->assertSee($campaign->name);
        $response->assertSee('Donate Now');
    }

    public function test_draft_campaign_detail_page_returns_404(): void
    {
        $campaign = $this->campaign(['status' => 'draft']);

        $this->get(route('donations.campaigns.show', $campaign))->assertNotFound();
    }

    public function test_donate_form_renders_with_enabled_payment_methods(): void
    {
        $campaign = $this->campaign();

        $response = $this->get(route('donations.donate', $campaign));

        $response->assertOk();
        $response->assertSee('Donation Amount');
        $response->assertSee('Bank Transfer');
    }

    public function test_donate_form_for_a_draft_campaign_returns_404(): void
    {
        $campaign = $this->campaign(['status' => 'draft']);

        $this->get(route('donations.donate', $campaign))->assertNotFound();
    }

    public function test_submitting_the_donate_form_creates_a_pending_donation_and_redirects_to_payment(): void
    {
        Mail::fake();

        $campaign = $this->campaign();

        $response = $this->post(route('donations.store'), [
            'donation_campaign_id' => $campaign->id,
            'donor_name' => 'Ramesh Kulkarni',
            'donor_email' => 'ramesh@example.com',
            'donor_phone' => '9822001122',
            'amount' => 1101,
            'payment_method' => 'bank_transfer',
        ]);

        $donation = Donation::firstOrFail();

        $response->assertRedirect(route('donations.pay', $donation));
        $this->assertSame('pending', $donation->payment_status->value);
        $this->assertSame($campaign->id, $donation->donation_campaign_id);
        $this->assertNotNull($donation->reference);

        Mail::assertQueued(NewDonationNotificationMail::class);
    }

    public function test_honeypot_field_blocks_bot_submissions(): void
    {
        $this->post(route('donations.store'), [
            'donor_name' => 'Bot',
            'donor_email' => 'bot@example.com',
            'amount' => 500,
            'payment_method' => 'bank_transfer',
            'website' => 'https://spam.example',
        ])->assertSessionHasErrors('website');

        $this->assertSame(0, Donation::count());
    }

    public function test_payment_page_shows_offline_instructions_for_bank_transfer(): void
    {
        Mail::fake();

        $this->post(route('donations.store'), [
            'donor_name' => 'Ramesh Kulkarni',
            'donor_email' => 'ramesh@example.com',
            'amount' => 1101,
            'payment_method' => 'bank_transfer',
        ]);

        $donation = Donation::firstOrFail();

        $response = $this->get(route('donations.pay', $donation));

        $response->assertOk();
        $response->assertSee('Bank Transfer');
        $response->assertSee($donation->reference);
    }

    public function test_success_page_renders_for_a_donation(): void
    {
        Mail::fake();
        $donation = Donation::create([
            'donor_name' => 'Meena Joshi',
            'donor_email' => 'meena@example.com',
            'amount' => 501,
            'payment_method' => 'upi',
            'payment_status' => 'pending',
            'donated_at' => now(),
        ]);

        $response = $this->get(route('donations.success', $donation));

        $response->assertOk();
        $response->assertSee('Thank You');
        $response->assertSee($donation->reference);
    }

    public function test_failure_page_renders_for_a_donation(): void
    {
        $donation = Donation::create([
            'donor_name' => 'Meena Joshi',
            'donor_email' => 'meena@example.com',
            'amount' => 501,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'failed',
            'donated_at' => now(),
        ]);

        $response = $this->get(route('donations.failed', $donation));

        $response->assertOk();
        $response->assertSee('Payment Not Completed');
    }

    public function test_completed_campaign_detail_hides_the_donate_button(): void
    {
        $campaign = $this->campaign(['status' => 'completed']);

        $response = $this->get(route('donations.campaigns.show', $campaign));

        $response->assertOk();
        $response->assertSee('not accepting donations');
    }
}
