<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_user_with_manage_users_permission_can_view_the_user_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->get(route('admin.users.index'));

        $response->assertOk();
    }

    public function test_user_without_manage_users_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $response = $this->actingAs($viewer)->get(route('admin.users.index'));

        $response->assertForbidden();
    }

    public function test_admin_can_create_a_user_with_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Jane Volunteer',
            'email' => 'jane@example.com',
            'status' => 'active',
            'password' => 'Secret1234!',
            'password_confirmation' => 'Secret1234!',
            'roles' => [RoleEnum::VolunteerManager->value],
        ]);

        $response->assertRedirect(route('admin.users.index'));

        $created = User::where('email', 'jane@example.com')->firstOrFail();
        $this->assertTrue($created->hasRole(RoleEnum::VolunteerManager->value));
    }

    public function test_a_user_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertForbidden();
        $this->assertModelExists($admin);
    }

    public function test_a_super_admin_cannot_delete_their_own_account(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)->delete(route('admin.users.destroy', $superAdmin));

        $response->assertSessionHasErrors('user');
        $this->assertModelExists($superAdmin);
    }

    public function test_the_last_super_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $lastSuperAdmin = User::factory()->create();
        $lastSuperAdmin->assignRole(RoleEnum::SuperAdmin->value);

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $lastSuperAdmin));

        $response->assertSessionHasErrors('user');
        $this->assertModelExists($lastSuperAdmin);
    }
}
