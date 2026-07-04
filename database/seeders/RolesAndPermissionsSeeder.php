<?php

namespace Database\Seeders;

use App\Enums\Permission as PermissionEnum;
use App\Enums\Role as RoleEnum;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Default permission grants per role, beyond Super Admin (which always
     * receives every permission) and Viewer (which intentionally receives none).
     *
     * @var array<string, list<string>>
     */
    private const ROLE_PERMISSIONS = [
        RoleEnum::Admin->value => [
            PermissionEnum::ManageUsers,
            PermissionEnum::ManageHomepage,
            PermissionEnum::ManageAbout,
            PermissionEnum::ManageActivities,
            PermissionEnum::ManageGallery,
            PermissionEnum::ManageBlog,
            PermissionEnum::ManageEvents,
            PermissionEnum::ManageDonations,
            PermissionEnum::ManageVolunteers,
            PermissionEnum::ManageReports,
            PermissionEnum::ManageSettings,
            PermissionEnum::ManageContactMessages,
        ],
        RoleEnum::ContentManager->value => [
            PermissionEnum::ManageHomepage,
            PermissionEnum::ManageAbout,
            PermissionEnum::ManageActivities,
            PermissionEnum::ManageGallery,
            PermissionEnum::ManageBlog,
            PermissionEnum::ManageEvents,
        ],
        RoleEnum::VolunteerManager->value => [
            PermissionEnum::ManageVolunteers,
        ],
        RoleEnum::DonationManager->value => [
            PermissionEnum::ManageDonations,
            PermissionEnum::ManageReports,
        ],
        RoleEnum::Editor->value => [
            PermissionEnum::ManageBlog,
            PermissionEnum::ManageActivities,
        ],
    ];

    /**
     * Seed the roles and permissions.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (PermissionEnum::values() as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        foreach (RoleEnum::values() as $roleName) {
            Role::findOrCreate($roleName, 'web');
        }

        // Guards against a cold cache store returning a stale (empty) permission
        // list to the syncPermissions() calls below on a fresh, single-shot seed.
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        Role::findByName(RoleEnum::SuperAdmin->value)->syncPermissions(PermissionEnum::values());
        Role::findByName(RoleEnum::Viewer->value)->syncPermissions([]);

        foreach (self::ROLE_PERMISSIONS as $roleName => $permissions) {
            Role::findByName($roleName)->syncPermissions(
                array_map(fn (PermissionEnum $permission) => $permission->value, $permissions)
            );
        }
    }
}
