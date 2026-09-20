<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use App\Services\SitemapService;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __construct(
        private SitemapService $sitemap,
        private SettingsService $settings,
    ) {}

    public function index(): Response
    {
        // A site marked "discourage indexing" (staging) advertises nothing.
        abort_if($this->settings->get('seo.discourage_indexing', false) || ! $this->settings->get('seo.sitemap_enabled', true), 404);

        return response($this->sitemap->render(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'X-Robots-Tag' => 'noindex',
        ]);
    }
}
