<?php

namespace App\Services;

use App\Enums\Role;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserService
{
    public function __construct(private UserRepositoryInterface $users) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data): User
    {
        $user = $this->users->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
            'password' => $data['password'],
        ]);

        $user->syncRoles($data['roles']);

        return $user;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(User $user, array $data): User
    {
        $attributes = [
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'status' => $data['status'],
        ];

        if (! empty($data['password'])) {
            $attributes['password'] = $data['password'];
        }

        $this->users->update($user, $attributes);
        $user->syncRoles($data['roles']);

        return $user->refresh();
    }

    public function deleteUser(User $user, User $actingUser): bool
    {
        if ($actingUser->is($user)) {
            throw ValidationException::withMessages([
                'user' => 'You cannot delete your own account.',
            ]);
        }

        if ($user->hasRole(Role::SuperAdmin->value) && User::role(Role::SuperAdmin->value)->count() <= 1) {
            throw ValidationException::withMessages([
                'user' => 'The last Super Admin account cannot be deleted.',
            ]);
        }

        return $this->users->delete($user);
    }
}
