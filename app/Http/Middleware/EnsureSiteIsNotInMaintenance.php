<?php

namespace App\Http\Middleware;

use App\Services\SettingsService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Settings-driven maintenance mode for the public website.
 *
 * Unlike `php artisan down` (which blocks everything, admins included)
 * this only gates public routes: the admin panel, the auth routes needed
 * to reach it, signed-in users who can manage settings, and whitelisted
 * IPs all pass through — so an administrator can never lock themselves
 * out by flipping the switch.
 */
class EnsureSiteIsNotInMaintenance
{
    public function __construct(private SettingsService $settings) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->settings->get('maintenance.enabled', false)) {
            return $next($request);
        }

        if ($this->isExempt($request)) {
            return $next($request);
        }

        // Never let the local debug toolbar decorate the maintenance page.
        if (app()->bound('debugbar')) {
            app('debugbar')->disable();
        }

        return response()->view('frontend.maintenance', [
            'heading' => $this->settings->get('maintenance.heading', 'We’ll be back shortly'),
            'message' => $this->settings->get('maintenance.message'),
            'expectedBackAt' => $this->settings->get('maintenance.expected_back_at'),
        ], 503)->header('Retry-After', '3600');
    }

    private function isExempt(Request $request): bool
    {
        // Admin panel, login/password flows, health check and storage.
        if ($request->is('admin', 'admin/*', 'login', 'logout', 'forgot-password', 'reset-password', 'reset-password/*', 'up', 'storage/*', 'robots.txt')) {
            return true;
        }

        $user = $request->user();

        if ($user && ($user->hasRole('Super Admin') || $user->can('manage settings'))) {
            return true;
        }

        $allowed = preg_split('/[\s,]+/', (string) $this->settings->get('maintenance.allowed_ips', ''), -1, PREG_SPLIT_NO_EMPTY);

        return in_array($request->ip(), $allowed, true);
    }
}
