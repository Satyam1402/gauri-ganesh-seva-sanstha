<?php

namespace App\Providers;

use App\Services\SettingsService;
use Illuminate\Support\ServiceProvider;

/**
 * Makes database-managed settings the source of truth for values the
 * framework and existing modules read from config(): the app name (used
 * by 120+ templates and every mail subject), the mail sender name, the
 * timezone, donation defaults and offline payment details.
 *
 * Only non-secret values are overridden. Gateway secrets, SMTP passwords
 * and API keys remain env-only and are never touched here.
 */
class SettingsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(SettingsService::class);
    }

    public function boot(): void
    {
        // Artisan commands that run before the schema exists (migrate,
        // key:generate) must not depend on settings.
        if ($this->app->runningInConsole() && ! $this->app->runningUnitTests()) {
            return;
        }

        $this->app->make(SettingsService::class)->applyConfigOverrides();
    }
}
