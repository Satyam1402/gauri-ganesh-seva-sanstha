<?php

namespace App\Services;

use App\Enums\Role as RoleEnum;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class RoleService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function createRole(array $data): Role
    {
        $role = Role::create(['name' => $data['name'], 'guard_name' => 'web']);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        if ($role->name === RoleEnum::SuperAdmin->value && $data['name'] !== RoleEnum::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'name' => 'The Super Admin role cannot be renamed.',
            ]);
        }

        $role->update(['name' => $data['name']]);
        $role->syncPermissions($data['permissions'] ?? []);

        return $role->refresh();
    }

    public function deleteRole(Role $role): bool
    {
        if ($role->name === RoleEnum::SuperAdmin->value) {
            throw ValidationException::withMessages([
                'role' => 'The Super Admin role cannot be deleted.',
            ]);
        }

        return (bool) $role->delete();
    }
}
