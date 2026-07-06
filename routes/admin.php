<?php

use App\Http\Controllers\Admin\AboutSectionController;
use App\Http\Controllers\Admin\ActivityCategoryController;
use App\Http\Controllers\Admin\ActivityController;
use App\Http\Controllers\Admin\BlogCategoryController;
use App\Http\Controllers\Admin\BlogCommentController;
use App\Http\Controllers\Admin\BlogPostController;
use App\Http\Controllers\Admin\ContactEnquiryController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\DonationCampaignController;
use App\Http\Controllers\Admin\DonationController;
use App\Http\Controllers\Admin\DonationReportController;
use App\Http\Controllers\Admin\EventCategoryController;
use App\Http\Controllers\Admin\EventController;
use App\Http\Controllers\Admin\EventRegistrationController;
use App\Http\Controllers\Admin\GalleryAlbumController;
use App\Http\Controllers\Admin\GalleryCategoryController;
use App\Http\Controllers\Admin\GalleryPhotoController;
use App\Http\Controllers\Admin\GalleryVideoController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\OrgProfileController;
use App\Http\Controllers\Admin\PageSeoController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
use App\Http\Controllers\Admin\VolunteerApplicationController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('profile/password', [PasswordController::class, 'update'])->name('password.update');

    Route::middleware('permission:manage users')->group(function () {
        Route::resource('users', UserController::class)->except(['show']);
    });

    Route::middleware('permission:manage roles')->group(function () {
        Route::resource('roles', RoleController::class)->except(['show']);
    });

    Route::middleware('permission:manage homepage')->group(function () {
        Route::get('home-sections', [HomeSectionController::class, 'index'])->name('home-sections.index');
        Route::get('home-sections/{homeSection}/edit', [HomeSectionController::class, 'edit'])->name('home-sections.edit');
        Route::put('home-sections/{homeSection}', [HomeSectionController::class, 'update'])->name('home-sections.update');
        Route::patch('home-sections/{homeSection}/toggle', [HomeSectionController::class, 'toggle'])->name('home-sections.toggle');
        Route::post('home-sections/reorder', [HomeSectionController::class, 'reorder'])->name('home-sections.reorder');

        Route::get('pages/{page}/seo', [PageSeoController::class, 'edit'])->name('pages.seo.edit');
        Route::put('pages/{page}/seo', [PageSeoController::class, 'update'])->name('pages.seo.update');
    });

    Route::middleware('permission:manage about')->group(function () {
        Route::get('about-sections', [AboutSectionController::class, 'index'])->name('about-sections.index');
        Route::get('about-sections/{aboutSection}/edit', [AboutSectionController::class, 'edit'])->name('about-sections.edit');
        Route::put('about-sections/{aboutSection}', [AboutSectionController::class, 'update'])->name('about-sections.update');
        Route::patch('about-sections/{aboutSection}/toggle', [AboutSectionController::class, 'toggle'])->name('about-sections.toggle');
        Route::post('about-sections/reorder', [AboutSectionController::class, 'reorder'])->name('about-sections.reorder');

        Route::get('about/org-profile', [OrgProfileController::class, 'edit'])->name('org-profile.edit');
        Route::put('about/org-profile', [OrgProfileController::class, 'update'])->name('org-profile.update');
    });

    Route::middleware('permission:manage activities')->group(function () {
        Route::resource('activity-categories', ActivityCategoryController::class)->except(['show']);
        Route::post('activity-categories/reorder', [ActivityCategoryController::class, 'reorder'])->name('activity-categories.reorder');

        Route::resource('activities', ActivityController::class)->except(['show']);
        Route::patch('activities/{activity}/feature', [ActivityController::class, 'toggleFeatured'])->name('activities.feature');
        Route::patch('activities/{activity}/publish', [ActivityController::class, 'publish'])->name('activities.publish');
        Route::patch('activities/{activity}/unpublish', [ActivityController::class, 'unpublish'])->name('activities.unpublish');
        Route::patch('activities/{activity}/restore', [ActivityController::class, 'restore'])->name('activities.restore')->withTrashed();
        Route::post('activities/bulk-delete', [ActivityController::class, 'bulkDestroy'])->name('activities.bulk-delete');
        Route::post('activities/bulk-publish', [ActivityController::class, 'bulkPublish'])->name('activities.bulk-publish');
        Route::post('activities/bulk-category', [ActivityController::class, 'bulkUpdateCategory'])->name('activities.bulk-category');
    });

    Route::middleware('permission:manage donations')->group(function () {
        Route::post('donation-campaigns/reorder', [DonationCampaignController::class, 'reorder'])->name('donation-campaigns.reorder');
        Route::patch('donation-campaigns/{donation_campaign}/feature', [DonationCampaignController::class, 'toggleFeatured'])->name('donation-campaigns.feature');
        Route::patch('donation-campaigns/{donation_campaign}/activate', [DonationCampaignController::class, 'activate'])->name('donation-campaigns.activate');
        Route::patch('donation-campaigns/{donation_campaign}/archive', [DonationCampaignController::class, 'archive'])->name('donation-campaigns.archive');
        Route::patch('donation-campaigns/{donation_campaign}/restore', [DonationCampaignController::class, 'restore'])->name('donation-campaigns.restore')->withTrashed();
        Route::resource('donation-campaigns', DonationCampaignController::class)->except(['show']);

        // Registered before the resource so "export" is never captured by
        // the {donation} wildcard.
        Route::get('donations/export', [DonationController::class, 'export'])->name('donations.export');
        Route::patch('donations/{donation}/complete', [DonationController::class, 'markCompleted'])->name('donations.complete');
        Route::patch('donations/{donation}/fail', [DonationController::class, 'markFailed'])->name('donations.fail');
        Route::patch('donations/{donation}/restore', [DonationController::class, 'restore'])->name('donations.restore')->withTrashed();
        Route::resource('donations', DonationController::class);
    });

    Route::middleware('permission:manage reports|manage donations')->group(function () {
        Route::get('donation-reports', [DonationReportController::class, 'index'])->name('donation-reports.index');
    });

    Route::middleware('permission:manage blog')->group(function () {
        Route::resource('blog-categories', BlogCategoryController::class)->except(['show']);
        Route::post('blog-categories/reorder', [BlogCategoryController::class, 'reorder'])->name('blog-categories.reorder');

        Route::patch('blog-posts/{blog_post}/feature', [BlogPostController::class, 'toggleFeatured'])->name('blog-posts.feature');
        Route::patch('blog-posts/{blog_post}/publish', [BlogPostController::class, 'publish'])->name('blog-posts.publish');
        Route::patch('blog-posts/{blog_post}/unpublish', [BlogPostController::class, 'unpublish'])->name('blog-posts.unpublish');
        Route::patch('blog-posts/{blog_post}/restore', [BlogPostController::class, 'restore'])->name('blog-posts.restore')->withTrashed();
        Route::post('blog-posts/bulk-delete', [BlogPostController::class, 'bulkDestroy'])->name('blog-posts.bulk-delete');
        Route::post('blog-posts/bulk-publish', [BlogPostController::class, 'bulkPublish'])->name('blog-posts.bulk-publish');
        Route::resource('blog-posts', BlogPostController::class)->except(['show']);

        Route::get('blog-comments', [BlogCommentController::class, 'index'])->name('blog-comments.index');
        Route::put('blog-comments/{blog_comment}', [BlogCommentController::class, 'update'])->name('blog-comments.update');
        Route::delete('blog-comments/{blog_comment}', [BlogCommentController::class, 'destroy'])->name('blog-comments.destroy');
    });

    Route::middleware('permission:manage events')->group(function () {
        Route::resource('event-categories', EventCategoryController::class)->except(['show']);
        Route::post('event-categories/reorder', [EventCategoryController::class, 'reorder'])->name('event-categories.reorder');

        Route::patch('events/{event}/feature', [EventController::class, 'toggleFeatured'])->name('events.feature');
        Route::patch('events/{event}/publish', [EventController::class, 'publish'])->name('events.publish');
        Route::patch('events/{event}/unpublish', [EventController::class, 'unpublish'])->name('events.unpublish');
        Route::patch('events/{event}/cancel', [EventController::class, 'cancel'])->name('events.cancel');
        Route::patch('events/{event}/restore', [EventController::class, 'restore'])->name('events.restore')->withTrashed();
        Route::post('events/bulk-delete', [EventController::class, 'bulkDestroy'])->name('events.bulk-delete');
        Route::post('events/bulk-publish', [EventController::class, 'bulkPublish'])->name('events.bulk-publish');
        Route::resource('events', EventController::class)->except(['show']);

        // Registered before the wildcard routes so "export" is never
        // captured by the {event_registration} parameter.
        Route::get('event-registrations/export', [EventRegistrationController::class, 'export'])->name('event-registrations.export');
        Route::get('event-registrations', [EventRegistrationController::class, 'index'])->name('event-registrations.index');
        Route::put('event-registrations/{event_registration}', [EventRegistrationController::class, 'update'])->name('event-registrations.update');
        Route::delete('event-registrations/{event_registration}', [EventRegistrationController::class, 'destroy'])->name('event-registrations.destroy');
    });

    Route::middleware('permission:manage contact messages')->group(function () {
        // Registered before the wildcard routes so "export" and the bulk
        // endpoints are never captured by {contact_enquiry}.
        Route::get('contact-enquiries/export', [ContactEnquiryController::class, 'export'])->name('contact-enquiries.export');
        Route::post('contact-enquiries/bulk-status', [ContactEnquiryController::class, 'bulkUpdateStatus'])->name('contact-enquiries.bulk-status');
        Route::post('contact-enquiries/bulk-delete', [ContactEnquiryController::class, 'bulkDestroy'])->name('contact-enquiries.bulk-delete');

        Route::get('contact-enquiries', [ContactEnquiryController::class, 'index'])->name('contact-enquiries.index');
        Route::get('contact-enquiries/{contact_enquiry}', [ContactEnquiryController::class, 'show'])->name('contact-enquiries.show')->withTrashed();
        Route::put('contact-enquiries/{contact_enquiry}', [ContactEnquiryController::class, 'update'])->name('contact-enquiries.update');
        Route::post('contact-enquiries/{contact_enquiry}/reply', [ContactEnquiryController::class, 'reply'])->name('contact-enquiries.reply');
        Route::patch('contact-enquiries/{contact_enquiry}/restore', [ContactEnquiryController::class, 'restore'])->name('contact-enquiries.restore')->withTrashed();
        Route::delete('contact-enquiries/{contact_enquiry}', [ContactEnquiryController::class, 'destroy'])->name('contact-enquiries.destroy');
        Route::get('contact-enquiries/{contact_enquiry}/attachment', [ContactEnquiryController::class, 'downloadAttachment'])->name('contact-enquiries.attachment');
    });

    Route::middleware('permission:manage volunteers')->group(function () {
        // Registered before the wildcard routes so "export" and the bulk
        // endpoints are never captured by {volunteer_application}.
        Route::get('volunteer-applications/export', [VolunteerApplicationController::class, 'export'])->name('volunteer-applications.export');
        Route::post('volunteer-applications/bulk-status', [VolunteerApplicationController::class, 'bulkUpdateStatus'])->name('volunteer-applications.bulk-status');
        Route::post('volunteer-applications/bulk-delete', [VolunteerApplicationController::class, 'bulkDestroy'])->name('volunteer-applications.bulk-delete');

        Route::get('volunteer-applications', [VolunteerApplicationController::class, 'index'])->name('volunteer-applications.index');
        Route::get('volunteer-applications/{volunteer_application}', [VolunteerApplicationController::class, 'show'])->name('volunteer-applications.show')->withTrashed();
        Route::put('volunteer-applications/{volunteer_application}', [VolunteerApplicationController::class, 'update'])->name('volunteer-applications.update');
        Route::patch('volunteer-applications/{volunteer_application}/approve', [VolunteerApplicationController::class, 'approve'])->name('volunteer-applications.approve');
        Route::patch('volunteer-applications/{volunteer_application}/reject', [VolunteerApplicationController::class, 'reject'])->name('volunteer-applications.reject');
        Route::patch('volunteer-applications/{volunteer_application}/hold', [VolunteerApplicationController::class, 'hold'])->name('volunteer-applications.hold');
        Route::patch('volunteer-applications/{volunteer_application}/archive', [VolunteerApplicationController::class, 'archive'])->name('volunteer-applications.archive');
        Route::patch('volunteer-applications/{volunteer_application}/restore', [VolunteerApplicationController::class, 'restore'])->name('volunteer-applications.restore')->withTrashed();
        Route::delete('volunteer-applications/{volunteer_application}', [VolunteerApplicationController::class, 'destroy'])->name('volunteer-applications.destroy');
        Route::get('volunteer-applications/{volunteer_application}/documents/{collection}', [VolunteerApplicationController::class, 'downloadDocument'])->name('volunteer-applications.document');
    });

    Route::middleware('permission:manage gallery')->group(function () {
        Route::resource('gallery-categories', GalleryCategoryController::class)->except(['show']);
        Route::post('gallery-categories/reorder', [GalleryCategoryController::class, 'reorder'])->name('gallery-categories.reorder');

        Route::patch('gallery-albums/{gallery_album}/feature', [GalleryAlbumController::class, 'toggleFeatured'])->name('gallery-albums.feature');
        Route::patch('gallery-albums/{gallery_album}/publish', [GalleryAlbumController::class, 'publish'])->name('gallery-albums.publish');
        Route::patch('gallery-albums/{gallery_album}/unpublish', [GalleryAlbumController::class, 'unpublish'])->name('gallery-albums.unpublish');
        Route::patch('gallery-albums/{gallery_album}/restore', [GalleryAlbumController::class, 'restore'])->name('gallery-albums.restore')->withTrashed();
        Route::resource('gallery-albums', GalleryAlbumController::class)->except(['show']);

        Route::post('gallery-albums/{gallery_album}/photos', [GalleryPhotoController::class, 'store'])->name('gallery-photos.store');
        Route::post('gallery-albums/{gallery_album}/photos/reorder', [GalleryPhotoController::class, 'reorder'])->name('gallery-photos.reorder');
        Route::post('gallery-albums/{gallery_album}/photos/bulk-delete', [GalleryPhotoController::class, 'bulkDestroy'])->name('gallery-photos.bulk-delete');
        Route::put('gallery-albums/{gallery_album}/photos/{photo}', [GalleryPhotoController::class, 'update'])->name('gallery-photos.update');
        Route::patch('gallery-albums/{gallery_album}/photos/{photo}/toggle', [GalleryPhotoController::class, 'toggle'])->name('gallery-photos.toggle');
        Route::delete('gallery-albums/{gallery_album}/photos/{photo}', [GalleryPhotoController::class, 'destroy'])->name('gallery-photos.destroy');

        Route::post('gallery-albums/{gallery_album}/videos', [GalleryVideoController::class, 'store'])->name('gallery-videos.store');
        Route::put('gallery-albums/{gallery_album}/videos/{video}', [GalleryVideoController::class, 'update'])->name('gallery-videos.update');
        Route::delete('gallery-albums/{gallery_album}/videos/{video}', [GalleryVideoController::class, 'destroy'])->name('gallery-videos.destroy');
    });
});
