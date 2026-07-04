<?php

namespace Tests\Feature\Admin;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_only_super_admin_can_manage_roles(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        $response = $this->actingAs($admin)->get(route('admin.roles.index'));

        $response->assertForbidden();
    }

    public function test_super_admin_can_create_a_role_with_permissions(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);

        $response = $this->actingAs($superAdmin)->post(route('admin.roles.store'), [
            'name' => 'Auditor',
            'permissions' => [PermissionEnum::ManageReports->value],
        ]);

        $response->assertRedirect(route('admin.roles.index'));

        $role = Role::findByName('Auditor', 'web');
        $this->assertTrue($role->hasPermissionTo(PermissionEnum::ManageReports->value));
    }

    public function test_super_admin_role_cannot_be_renamed(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);

        $role = Role::findByName(RoleEnum::SuperAdmin->value, 'web');

        $response = $this->actingAs($superAdmin)->put(route('admin.roles.update', $role), [
            'name' => 'Not Super Admin',
        ]);

        $response->assertSessionHasErrors('name');
        $this->assertSame(RoleEnum::SuperAdmin->value, $role->fresh()->name);
    }

    public function test_super_admin_role_cannot_be_deleted(): void
    {
        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(RoleEnum::SuperAdmin->value);

        $role = Role::findByName(RoleEnum::SuperAdmin->value, 'web');

        $response = $this->actingAs($superAdmin)->delete(route('admin.roles.destroy', $role));

        $response->assertSessionHasErrors('role');
        $this->assertModelExists($role);
    }
}
