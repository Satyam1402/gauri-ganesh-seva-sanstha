<?php

namespace App\Services;

use App\Models\Activity;
use App\Models\BlogCategory;
use App\Models\BlogPost;
use App\Models\BlogTag;
use App\Models\DonationCampaign;
use App\Models\Event;
use App\Models\GalleryAlbum;
use App\Models\Partner;
use App\Models\SlugRedirect;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * When a public URL 404s, checks whether its slug segment was renamed and
 * issues a permanent redirect to the new address. Invoked from the
 * exception handler (bootstrap/app.php) so it never runs on normal hits.
 */
class SlugRedirectService
{
    /**
     * URL prefix → [model class, segment index of the slug].
     *
     * @var array<string, array{0: class-string, 1: int}>
     */
    private const PREFIXES = [
        'activities' => [Activity::class, 1],
        'blog/category' => [BlogCategory::class, 2],
        'blog/tag' => [BlogTag::class, 2],
        'blog' => [BlogPost::class, 1],
        'events' => [Event::class, 1],
        'gallery' => [GalleryAlbum::class, 1],
        'campaigns' => [DonationCampaign::class, 1],
        'donate' => [DonationCampaign::class, 1],
        'partners' => [Partner::class, 1],
    ];

    public function resolve(Request $request): ?RedirectResponse
    {
        if (! $request->isMethod('GET')) {
            return null;
        }

        $segments = $request->segments();

        foreach (self::PREFIXES as $prefix => [$class, $index]) {
            $prefixSegments = explode('/', $prefix);

            if (array_slice($segments, 0, count($prefixSegments)) !== $prefixSegments || count($segments) !== $index + 1) {
                continue;
            }

            $target = $this->follow((new $class)->getMorphClass(), $segments[$index]);

            if ($target === null) {
                return null;
            }

            $segments[$index] = $target;
            $url = url(implode('/', $segments));

            if ($query = $request->getQueryString()) {
                $url .= '?'.$query;
            }

            return redirect()->to($url, 301);
        }

        return null;
    }

    /**
     * Follow a redirect chain (bounded, in case of stale loops).
     */
    private function follow(string $type, string $slug): ?string
    {
        $current = $slug;

        for ($hop = 0; $hop < 5; $hop++) {
            $next = SlugRedirect::query()->where('model_type', $type)->where('old_slug', $current)->value('new_slug');

            if ($next === null) {
                return $hop === 0 ? null : $current;
            }

            $current = $next;
        }

        return $current;
    }
}
