<?php

namespace Database\Seeders;

use App\Enums\Role as RoleEnum;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class AdminUserSeeder extends Seeder
{
    /**
     * Seed the initial Super Admin account.
     *
     * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD when set; otherwise a
     * random password is generated and printed once so it can be captured.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'superadmin@ggss.org');

        if (User::withTrashed()->where('email', $email)->exists()) {
            return;
        }

        $password = env('ADMIN_PASSWORD');
        $generated = $password === null;
        $password ??= Str::password(16);

        $user = User::create([
            'name' => 'Super Admin',
            'email' => $email,
            'password' => $password,
            'status' => 'active',
        ]);

        $user->assignRole(RoleEnum::SuperAdmin->value);

        if ($generated) {
            $this->command?->warn("Super Admin created — email: {$email} / password: {$password}");
            $this->command?->warn('Store this password securely and change it after first login — it will not be shown again.');
        }
    }
}
