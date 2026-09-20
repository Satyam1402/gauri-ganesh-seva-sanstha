<?php

use App\Enums\MenuLocation;
use App\Interfaces\MenuItemRepositoryInterface;
use App\Models\MenuItem;
use App\Services\SettingsService;
use Illuminate\Database\Eloquent\Collection;

if (! function_exists('format_inr')) {
    /**
     * Format a numeric amount using Indian digit grouping (e.g. 1,00,000 rather than 100,000).
     * Avoids a hard dependency on the intl extension, which isn't guaranteed on every VPS.
     */
    function format_inr(float|int $amount, bool $withSymbol = true): string
    {
        $isNegative = $amount < 0;
        [$whole, $decimal] = array_pad(explode('.', number_format(abs($amount), 2, '.', '')), 2, '00');

        $lastThree = substr($whole, -3);
        $remaining = substr($whole, 0, -3);

        if ($remaining !== '') {
            $remaining = preg_replace('/\B(?=(\d{2})+(?!\d))/', ',', $remaining);
            $lastThree = ','.$lastThree;
        }

        $formatted = $remaining.$lastThree;
        $formatted = ($decimal === '00') ? $formatted : "{$formatted}.{$decimal}";

        return ($isNegative ? '-' : '').($withSymbol ? '₹' : '').$formatted;
    }
}

if (! function_exists('setting')) {
    /**
     * Read a site setting by "group.key" (e.g. setting('contact.phone_primary')).
     * Backed by one cached map — safe to call freely from Blade, mail
     * templates, jobs and notifications without touching the database.
     */
    function setting(string $key, mixed $default = null): mixed
    {
        return app(SettingsService::class)->get($key, $default);
    }
}

if (! function_exists('setting_media')) {
    /**
     * URL of a media setting such as setting_media('branding.logo', 'webp'),
     * or null when nothing has been uploaded.
     */
    function setting_media(string $key, ?string $conversion = null): ?string
    {
        return app(SettingsService::class)->mediaUrl($key, $conversion);
    }
}

if (! function_exists('site_menu')) {
    /**
     * Active menu tree for a location ("header", "footer_explore", ...).
     *
     * @return Collection<int, MenuItem>
     */
    function site_menu(string $location): Collection
    {
        return app(MenuItemRepositoryInterface::class)->tree(MenuLocation::from($location));
    }
}
