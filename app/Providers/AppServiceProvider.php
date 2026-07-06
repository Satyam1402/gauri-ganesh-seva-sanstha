<?php

namespace App\Providers;

use App\Interfaces\AboutSectionRepositoryInterface;
use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Interfaces\ActivityRepositoryInterface;
use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Interfaces\BlogCommentRepositoryInterface;
use App\Interfaces\BlogPostRepositoryInterface;
use App\Interfaces\ContactEnquiryRepositoryInterface;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Interfaces\DonationRepositoryInterface;
use App\Interfaces\EventCategoryRepositoryInterface;
use App\Interfaces\EventRegistrationRepositoryInterface;
use App\Interfaces\EventRepositoryInterface;
use App\Interfaces\GalleryAlbumRepositoryInterface;
use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Interfaces\HomeSectionRepositoryInterface;
use App\Interfaces\UserRepositoryInterface;
use App\Interfaces\VolunteerApplicationRepositoryInterface;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Repositories\AboutSectionRepository;
use App\Repositories\ActivityCategoryRepository;
use App\Repositories\ActivityRepository;
use App\Repositories\BlogCategoryRepository;
use App\Repositories\BlogCommentRepository;
use App\Repositories\BlogPostRepository;
use App\Repositories\ContactEnquiryRepository;
use App\Repositories\DonationCampaignRepository;
use App\Repositories\DonationRepository;
use App\Repositories\EventCategoryRepository;
use App\Repositories\EventRegistrationRepository;
use App\Repositories\EventRepository;
use App\Repositories\GalleryAlbumRepository;
use App\Repositories\GalleryCategoryRepository;
use App\Repositories\HomeSectionRepository;
use App\Repositories\UserRepository;
use App\Repositories\VolunteerApplicationRepository;
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
        $this->app->bind(ActivityCategoryRepositoryInterface::class, ActivityCategoryRepository::class);
        $this->app->bind(ActivityRepositoryInterface::class, ActivityRepository::class);
        $this->app->bind(DonationCampaignRepositoryInterface::class, DonationCampaignRepository::class);
        $this->app->bind(DonationRepositoryInterface::class, DonationRepository::class);
        $this->app->bind(GalleryCategoryRepositoryInterface::class, GalleryCategoryRepository::class);
        $this->app->bind(GalleryAlbumRepositoryInterface::class, GalleryAlbumRepository::class);
        $this->app->bind(EventCategoryRepositoryInterface::class, EventCategoryRepository::class);
        $this->app->bind(EventRepositoryInterface::class, EventRepository::class);
        $this->app->bind(EventRegistrationRepositoryInterface::class, EventRegistrationRepository::class);
        $this->app->bind(BlogCategoryRepositoryInterface::class, BlogCategoryRepository::class);
        $this->app->bind(BlogPostRepositoryInterface::class, BlogPostRepository::class);
        $this->app->bind(BlogCommentRepositoryInterface::class, BlogCommentRepository::class);
        $this->app->bind(VolunteerApplicationRepositoryInterface::class, VolunteerApplicationRepository::class);
        $this->app->bind(ContactEnquiryRepositoryInterface::class, ContactEnquiryRepository::class);
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
