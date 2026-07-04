<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\AboutSectionRepositoryInterface;
use App\Models\OrgProfile;
use App\Models\Page;
use App\Services\OrgProfileService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __construct(private AboutSectionRepositoryInterface $sections) {}

    public function index(): View
    {
        $page = Cache::rememberForever('pages.about', fn () => Page::query()
            ->where('slug', 'about')
            ->with(['seo.ogImage', 'media'])
            ->first()
        );

        $orgProfile = Cache::rememberForever(OrgProfileService::CACHE_KEY, fn () => OrgProfile::query()
            ->with('media')
            ->first()
        );

        return view('frontend.about', [
            'page' => $page,
            'orgProfile' => $orgProfile,
            'sections' => $this->sections->activeForAboutPage(),
        ]);
    }
}
