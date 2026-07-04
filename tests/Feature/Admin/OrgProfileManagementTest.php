<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\OrgProfile;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrgProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_without_permission_cannot_view_org_profile(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $response = $this->actingAs($viewer)->get(route('admin.org-profile.edit'));

        $response->assertForbidden();
    }

    public function test_admin_can_update_org_profile(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->put(route('admin.org-profile.update'), [
            'legal_name' => 'Updated Trust Name',
            'registration_no' => 'REG/2026/001',
            'established_year' => 2015,
        ]);

        $response->assertRedirect(route('admin.org-profile.edit'));

        $profile = OrgProfile::first();
        $this->assertSame('Updated Trust Name', $profile->legal_name);
        $this->assertSame('REG/2026/001', $profile->registration_no);
    }
}
