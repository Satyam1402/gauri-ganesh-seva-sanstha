<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\SettingsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

/**
 * Renders the legal documents entered under Settings → Legal. A page
 * only exists once an administrator has entered content — nothing is
 * generated or assumed.
 */
class LegalPageController extends Controller
{
    /**
     * URL slug → [settings key, page title].
     *
     * @var array<string, array{0: string, 1: string}>
     */
    public const PAGES = [
        'privacy-policy' => ['legal.privacy_policy', 'Privacy Policy'],
        'terms' => ['legal.terms', 'Terms & Conditions'],
        'donation-terms' => ['legal.donation_terms', 'Donation Terms'],
        'refund-policy' => ['legal.refund_policy', 'Refund Policy'],
        'volunteer-terms' => ['legal.volunteer_terms', 'Volunteer Terms'],
    ];

    public function __construct(private SettingsService $settings) {}

    public function show(Request $request, string $page): View|RedirectResponse
    {
        abort_unless(array_key_exists($page, self::PAGES), 404);

        // /legal/{page} is an alias — the short URL is the canonical one.
        if ($request->is('legal/*')) {
            return redirect()->route('legal.show.'.$page, status: 301);
        }

        [$key, $title] = self::PAGES[$page];
        $content = $this->settings->get($key);

        abort_if(blank($content), 404);

        return view('frontend.legal.show', [
            'seo' => $this->seo()->make([
                'title' => $title,
                'description' => $title.' of '.setting('general.site_name').'.',
                'canonical' => route('legal.show.'.$page),
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => $title]],
            ]),
            'slug' => $page,
            'title' => $title,
            'html' => Str::markdown($content, ['html_input' => 'strip', 'allow_unsafe_links' => false]),
            'updatedAt' => Setting::query()->where('group', 'legal')->where('key', Str::after($key, 'legal.'))->value('updated_at'),
        ]);
    }

    /**
     * Legal pages that currently have content, for footer links.
     *
     * @return array<string, string>  slug => title
     */
    public static function published(SettingsService $settings): array
    {
        $pages = [];

        foreach (self::PAGES as $slug => [$key, $title]) {
            if ($settings->has($key)) {
                $pages[$slug] = $title;
            }
        }

        return $pages;
    }
}
