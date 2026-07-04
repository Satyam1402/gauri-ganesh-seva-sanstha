<?php

namespace App\Providers;

use App\Interfaces\AboutSectionRepositoryInterface;
use App\Interfaces\HomeSectionRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Repositories\AboutSectionRepository;
use App\Repositories\HomeSectionRepository;
use App\Repositories\UserRepository;
use Illuminate\Auth\Middleware\RedirectIfAuthenticated;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Spatie\Permission\Models\Role;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(HomeSectionRepositoryInterface::class, HomeSectionRepository::class);
        $this->app->bind(AboutSectionRepositoryInterface::class, AboutSectionRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::before(fn (User $user) => $user->hasRole('Super Admin') ? true : null);

        Gate::policy(Role::class, RolePolicy::class);

        RedirectIfAuthenticated::redirectUsing(fn () => route('admin.dashboard'));
    }
}
