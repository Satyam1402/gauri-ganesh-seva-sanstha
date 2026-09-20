<?php

use App\Http\Controllers\Frontend\AboutController;
use App\Http\Controllers\Frontend\ActivityController;
use App\Http\Controllers\Frontend\BlogController;
use App\Http\Controllers\Frontend\ContactController;
use App\Http\Controllers\Frontend\DonationCampaignController;
use App\Http\Controllers\Frontend\DonationController;
use App\Http\Controllers\Frontend\EventController;
use App\Http\Controllers\Frontend\FaqController;
use App\Http\Controllers\Frontend\GalleryController;
use App\Http\Controllers\Frontend\HomeController;
use App\Http\Controllers\Frontend\LegalPageController;
use App\Http\Controllers\Frontend\PartnerController;
use App\Http\Controllers\Frontend\RobotsController;
use App\Http\Controllers\Frontend\SitemapController;
use App\Http\Controllers\Frontend\TestimonialController;
use App\Http\Controllers\Frontend\VolunteerController;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

// Crawler endpoints — generated from live content and Site Settings.
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/robots.txt', [RobotsController::class, 'index'])->name('robots');
Route::get('/about', [AboutController::class, 'index'])->name('about');

Route::get('/activities', [ActivityController::class, 'index'])->name('activities.index');
Route::get('/activities/{activity:slug}', [ActivityController::class, 'show'])->name('activities.show');

Route::get('/blog', [BlogController::class, 'index'])->name('blog.index');
Route::get('/blog/category/{category:slug}', [BlogController::class, 'category'])->name('blog.category');
Route::get('/blog/tag/{tag:slug}', [BlogController::class, 'tag'])->name('blog.tag');
Route::get('/blog/{post:slug}', [BlogController::class, 'show'])->name('blog.show');
Route::post('/blog/{post:slug}/comments', [BlogController::class, 'storeComment'])
    ->middleware('throttle:10,1')
    ->name('blog.comments.store');

Route::get('/events', [EventController::class, 'index'])->name('events.index');
Route::get('/events/{event:slug}', [EventController::class, 'show'])->name('events.show');
Route::post('/events/{event:slug}/register', [EventController::class, 'register'])
    ->middleware('throttle:10,1')
    ->name('events.register');

Route::get('/gallery', [GalleryController::class, 'index'])->name('gallery.index');
Route::get('/gallery/{album:slug}', [GalleryController::class, 'show'])->name('gallery.show');

Route::get('/campaigns', [DonationCampaignController::class, 'index'])->name('donations.campaigns.index');
Route::get('/campaigns/{campaign:slug}', [DonationCampaignController::class, 'show'])->name('donations.campaigns.show');

Route::get('/donate/{campaign:slug?}', [DonationController::class, 'create'])->name('donations.donate');
Route::post('/donate', [DonationController::class, 'store'])->name('donations.store');
Route::get('/donation/{donation:reference}/pay', [DonationController::class, 'pay'])->name('donations.pay');
Route::post('/donation/{donation:reference}/callback/{gateway}', [DonationController::class, 'callback'])->name('donations.callback');
Route::get('/donation/{donation:reference}/success', [DonationController::class, 'success'])->name('donations.success');
Route::get('/donation/{donation:reference}/failed', [DonationController::class, 'failed'])->name('donations.failed');

// Volunteer thank-you sits above the form routes for readability; the store
// endpoint is throttled tighter than other forms since it accepts uploads.
Route::get('/volunteer', [VolunteerController::class, 'create'])->name('volunteer.create');
Route::post('/volunteer', [VolunteerController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('volunteer.store');
Route::get('/volunteer/thank-you', [VolunteerController::class, 'thankYou'])->name('volunteer.thank-you');

Route::get('/testimonials', [TestimonialController::class, 'index'])->name('testimonials.index');

Route::get('/faq', [FaqController::class, 'index'])->name('faq.index');

Route::get('/partners', [PartnerController::class, 'index'])->name('partners.index');
Route::get('/partners/{partner:slug}', [PartnerController::class, 'show'])->name('partners.show');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');

// Legal documents from Settings → Legal. Each named alias lets the
// navigation editor link to a specific page; the generic route serves them.
foreach (array_keys(LegalPageController::PAGES) as $legalPage) {
    Route::get('/'.$legalPage, [LegalPageController::class, 'show'])->defaults('page', $legalPage)->name('legal.show.'.$legalPage);
}
Route::get('/legal/{page}', [LegalPageController::class, 'show'])->name('legal.show');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

require __DIR__.'/auth.php';
require __DIR__.'/admin.php';
