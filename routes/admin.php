<?php

use App\Http\Controllers\Admin\AboutSectionController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\HomeSectionController;
use App\Http\Controllers\Admin\OrgProfileController;
use App\Http\Controllers\Admin\PageSeoController;
use App\Http\Controllers\Admin\PasswordController;
use App\Http\Controllers\Admin\ProfileController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\UserController;
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
});
