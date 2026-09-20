<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Services\SettingsService;
use Illuminate\Http\Response;

/**
 * robots.txt generated from Site Settings. Public content and every
 * asset needed to render it stay crawlable; only private and
 * non-content paths are disallowed.
 */
class RobotsController extends Controller
{
    public function __construct(private SettingsService $settings) {}

    public function index(): Response
    {
        $lines = ['User-agent: *'];

        if ($this->settings->get('seo.discourage_indexing', false)) {
            $lines[] = 'Disallow: /';
        } else {
            foreach ([
                '/admin', '/admin/',
                '/login', '/logout', '/forgot-password', '/reset-password',
                '/donation/',            // per-donation payment, success and failure pages
                '/volunteer/thank-you',
                '/*?q=',                 // on-site search results
                '/*&q=',
            ] as $path) {
                $lines[] = 'Disallow: '.$path;
            }

            // Belt and braces: never block rendering resources.
            $lines[] = 'Allow: /build/';
            $lines[] = 'Allow: /storage/';

            if ($this->settings->get('seo.sitemap_enabled', true)) {
                $lines[] = '';
                $lines[] = 'Sitemap: '.route('sitemap');
            }
        }

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
