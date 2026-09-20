<?php

use App\Http\Middleware\EnsureSiteIsNotInMaintenance;
use App\Http\Middleware\RedirectTrailingSlash;
use App\Services\SlugRedirectService;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Settings-driven maintenance mode (admins stay in) — runs after the
        // session/auth middleware so the current user can be checked.
        $middleware->web(append: [EnsureSiteIsNotInMaintenance::class]);

        // One canonical URL form: /about, never /about/.
        $middleware->web(prepend: [RedirectTrailingSlash::class]);

        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        // Renamed slugs keep working: a 404 on a public content URL is
        // checked against slug_redirects and 301ed to the new address.
        $exceptions->render(function (NotFoundHttpException $e, $request) {
            return app(SlugRedirectService::class)->resolve($request);
        });
    })->create();
