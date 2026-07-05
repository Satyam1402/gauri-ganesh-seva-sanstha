<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\DonationCampaign;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DonationCampaignManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function campaign(array $overrides = []): DonationCampaign
    {
        return DonationCampaign::create(array_merge([
            'name' => 'Food Distribution',
            'short_description' => 'Meals for families facing food insecurity.',
            'full_description' => 'Detailed campaign description.',
            'goal_amount' => 500000,
            'status' => 'draft',
        ], $overrides));
    }

    public function test_user_without_manage_donations_permission_is_forbidden(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);

        $this->actingAs($contentManager)->get(route('admin.donation-campaigns.index'))->assertForbidden();
    }

    public function test_donation_manager_can_create_a_campaign_with_a_featured_image(): void
    {
        $manager = User::factory()->create();
        $manager->assignRole(RoleEnum::DonationManager->value);

        $response = $this->actingAs($manager)->post(route('admin.donation-campaigns.store'), [
            'name' => 'Blanket Distribution',
            'short_description' => 'Warm blankets before winter.',
            'full_description' => 'Full description of the blanket drive.',
            'goal_amount' => 150000,
            'status' => 'active',
            'is_featured' => 1,
            'featured_image' => UploadedFile::fake()->image('blankets.jpg'),
        ]);

        $campaign = DonationCampaign::firstOrFail();
        $response->assertRedirect(route('admin.donation-campaigns.edit', $campaign));

        $this->assertSame('Blanket Distribution', $campaign->name);
        $this->assertSame('blanket-distribution', $campaign->slug);
        $this->assertTrue($campaign->is_featured);
        $this->assertNotNull($campaign->getFirstMedia('featured_image'));
    }

    public function test_updating_a_campaign_saves_seo_fields(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $campaign = $this->campaign();

        $response = $this->actingAs($admin)->put(route('admin.donation-campaigns.update', $campaign), [
            'name' => $campaign->name,
            'short_description' => $campaign->short_description,
            'full_description' => $campaign->full_description,
            'goal_amount' => 500000,
            'status' => 'draft',
            'meta_title' => 'Feed a Family Today',
            'meta_description' => 'Custom SEO description.',
        ]);

        $response->assertRedirect(route('admin.donation-campaigns.edit', $campaign));
        $this->assertSame('Feed a Family Today', $campaign->fresh()->seo->meta_title);
    }

    public function test_activate_and_archive_toggle_the_status(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $campaign = $this->campaign();

        $this->actingAs($admin)->patch(route('admin.donation-campaigns.activate', $campaign))->assertRedirect();
        $this->assertSame('active', $campaign->fresh()->status->value);

        $this->actingAs($admin)->patch(route('admin.donation-campaigns.archive', $campaign))->assertRedirect();
        $this->assertSame('archived', $campaign->fresh()->status->value);
    }

    public function test_toggling_featured_flips_the_flag(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $campaign = $this->campaign();
        $this->assertFalse($campaign->fresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.donation-campaigns.feature', $campaign))->assertRedirect();
        $this->assertTrue($campaign->fresh()->is_featured);
    }

    public function test_deleting_a_campaign_soft_deletes_it_and_it_can_be_restored(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $campaign = $this->campaign();

        $this->actingAs($admin)->delete(route('admin.donation-campaigns.destroy', $campaign))->assertRedirect();
        $this->assertSoftDeleted($campaign);

        $this->actingAs($admin)->patch(route('admin.donation-campaigns.restore', $campaign))->assertRedirect();
        $this->assertNull($campaign->fresh()->deleted_at);
    }

    public function test_reorder_updates_display_order(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $first = $this->campaign(['name' => 'Campaign One', 'order_column' => 1]);
        $second = $this->campaign(['name' => 'Campaign Two', 'order_column' => 2]);

        $this->actingAs($admin)->post(route('admin.donation-campaigns.reorder'), [
            'order' => [$second->id, $first->id],
        ])->assertOk();

        $this->assertSame(1, $second->fresh()->order_column);
        $this->assertSame(2, $first->fresh()->order_column);
    }
}
