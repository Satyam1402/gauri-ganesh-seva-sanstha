<?php

namespace Tests\Feature\Admin;

use App\Enums\NotificationCategory;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use App\Notifications\Contact\NewEnquiryAlert;
use App\Notifications\Donations\NewDonationAlert;
use App\Notifications\System\SystemAlert;
use App\Notifications\Volunteers\NewVolunteerApplicationAlert;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function userWithRole(RoleEnum $role): User
    {
        $user = User::factory()->create();
        $user->assignRole($role->value);

        return $user->fresh();
    }

    /**
     * Insert a delivered in-app notification directly, bypassing dispatch,
     * so tests can model rows that pre-date a permission change.
     */
    private function deliver(User $user, string $type, array $overrides = []): DatabaseNotification
    {
        $category = $type::category()->value;

        return $user->notifications()->create(array_merge([
            'id' => (string) Str::uuid(),
            'type' => $type,
            'data' => [
                'category' => $category,
                'title' => "Test {$category} notification",
                'message' => 'Details line',
                'url' => route('admin.dashboard'),
            ],
            'read_at' => null,
        ], $overrides));
    }

    public function test_guests_are_redirected_to_login(): void
    {
        $this->get(route('admin.notifications.index'))->assertRedirect(route('login'));
        $this->post(route('admin.notifications.read-all'))->assertRedirect(route('login'));
    }

    public function test_the_notification_centre_lists_only_categories_the_user_is_authorised_for(): void
    {
        $manager = $this->userWithRole(RoleEnum::DonationManager);

        $this->deliver($manager, NewDonationAlert::class, ['data' => ['category' => 'donation', 'title' => 'Visible donation alert', 'message' => '', 'url' => null]]);
        // A stale row from before the user lost "manage volunteers".
        $this->deliver($manager, NewVolunteerApplicationAlert::class, ['data' => ['category' => 'volunteer', 'title' => 'Hidden volunteer alert', 'message' => '', 'url' => null]]);

        $response = $this->actingAs($manager)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('Visible donation alert');
        $response->assertDontSee('Hidden volunteer alert');
        $this->assertSame(['donation'], array_keys($response->viewData('categories')));
        $this->assertSame(1, $response->viewData('unreadCount'));
    }

    public function test_super_admin_sees_every_category_and_the_bell_shows_the_unread_count(): void
    {
        $superAdmin = $this->userWithRole(RoleEnum::SuperAdmin);

        $this->deliver($superAdmin, NewDonationAlert::class);
        $this->deliver($superAdmin, NewVolunteerApplicationAlert::class);
        $this->deliver($superAdmin, SystemAlert::class, ['read_at' => now()]);

        $response = $this->actingAs($superAdmin)->get(route('admin.dashboard'));

        $response->assertOk();
        $response->assertSee('data-unread-count', escape: false);
        $response->assertSee('aria-label="Notifications, 2 unread"', escape: false);

        $index = $this->actingAs($superAdmin)->get(route('admin.notifications.index'));
        $this->assertCount(3, $index->viewData('notifications'));
        $this->assertCount(count(NotificationCategory::cases()), $index->viewData('categories'));
    }

    public function test_status_and_category_filters_scope_the_list(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);

        $unreadDonation = $this->deliver($admin, NewDonationAlert::class);
        $readEnquiry = $this->deliver($admin, NewEnquiryAlert::class, ['read_at' => now()]);

        $unread = $this->actingAs($admin)->get(route('admin.notifications.index', ['status' => 'unread']));
        $this->assertSame([$unreadDonation->id], $unread->viewData('notifications')->pluck('id')->all());

        $read = $this->actingAs($admin)->get(route('admin.notifications.index', ['status' => 'read']));
        $this->assertSame([$readEnquiry->id], $read->viewData('notifications')->pluck('id')->all());

        $enquiries = $this->actingAs($admin)->get(route('admin.notifications.index', ['category' => 'enquiry']));
        $this->assertSame([$readEnquiry->id], $enquiries->viewData('notifications')->pluck('id')->all());

        // A category the user is not authorised for yields nothing, never a leak.
        $system = $this->actingAs($admin)->get(route('admin.notifications.index', ['category' => 'system']));
        $this->assertCount(0, $system->viewData('notifications'));
    }

    public function test_a_user_can_mark_one_notification_as_read_but_not_someone_elses(): void
    {
        $owner = $this->userWithRole(RoleEnum::Admin);
        $other = $this->userWithRole(RoleEnum::Admin);
        $notification = $this->deliver($owner, NewDonationAlert::class);

        $this->actingAs($other)->patch(route('admin.notifications.read', $notification->id))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        auth()->logout();

        $this->actingAs($owner)->patch(route('admin.notifications.read', $notification->id))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_marking_all_as_read_only_touches_the_users_visible_notifications(): void
    {
        $manager = $this->userWithRole(RoleEnum::DonationManager);
        $other = $this->userWithRole(RoleEnum::Admin);

        $mine = $this->deliver($manager, NewDonationAlert::class);
        $stale = $this->deliver($manager, NewVolunteerApplicationAlert::class);
        $theirs = $this->deliver($other, NewDonationAlert::class);

        $this->actingAs($manager)->post(route('admin.notifications.read-all'))->assertRedirect();

        $this->assertNotNull($mine->fresh()->read_at);
        $this->assertNull($stale->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_opening_a_notification_marks_it_read_and_redirects_to_its_record(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);
        $notification = $this->deliver($admin, NewDonationAlert::class, ['data' => ['category' => 'donation', 'title' => 'T', 'message' => '', 'url' => route('admin.donations.index')]]);
        $withoutUrl = $this->deliver($admin, NewDonationAlert::class, ['data' => ['category' => 'donation', 'title' => 'T', 'message' => '', 'url' => null]]);

        $this->actingAs($admin)->get(route('admin.notifications.open', $notification->id))->assertRedirect(route('admin.donations.index'));
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($admin)->get(route('admin.notifications.open', $withoutUrl->id))->assertRedirect(route('admin.notifications.index'));

        $this->actingAs($admin)->get(route('admin.notifications.open', (string) Str::uuid()))->assertRedirect(route('admin.notifications.index'));
    }

    public function test_a_user_can_delete_a_notification_and_clear_read_ones(): void
    {
        $admin = $this->userWithRole(RoleEnum::Admin);
        $other = $this->userWithRole(RoleEnum::Admin);

        $toDelete = $this->deliver($admin, NewDonationAlert::class);
        $readOne = $this->deliver($admin, NewEnquiryAlert::class, ['read_at' => now()]);
        $unreadOne = $this->deliver($admin, NewEnquiryAlert::class);
        $theirs = $this->deliver($other, NewEnquiryAlert::class, ['read_at' => now()]);

        $this->actingAs($admin)->delete(route('admin.notifications.destroy', $toDelete->id))->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $toDelete->id]);

        $this->actingAs($admin)->delete(route('admin.notifications.destroy', $theirs->id))->assertNotFound();

        $this->actingAs($admin)->delete(route('admin.notifications.clear-read'))->assertRedirect();
        $this->assertDatabaseMissing('notifications', ['id' => $readOne->id]);
        $this->assertDatabaseHas('notifications', ['id' => $unreadOne->id]);
        $this->assertDatabaseHas('notifications', ['id' => $theirs->id]);
    }

    public function test_the_empty_state_renders_for_a_viewer_with_no_notifications(): void
    {
        $viewer = $this->userWithRole(RoleEnum::Viewer);

        $response = $this->actingAs($viewer)->get(route('admin.notifications.index'));

        $response->assertOk();
        $response->assertSee('No notifications');
        $this->assertSame([], $response->viewData('categories'));
    }
}
