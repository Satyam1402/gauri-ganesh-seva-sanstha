<?php

namespace App\Http\Controllers;

use App\Services\SeoService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    use AuthorizesRequests;

    /**
     * Metadata builder for public pages — every frontend action passes a
     * resolved SeoData object to its view as `seo`.
     */
    protected function seo(): SeoService
    {
        return app(SeoService::class);
    }
}
