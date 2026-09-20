<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\AboutSectionRepositoryInterface;
use App\Models\OrgProfile;
use App\Services\OrgProfileService;
use App\Support\Seo\StructuredData;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class AboutController extends Controller
{
    public function __construct(private AboutSectionRepositoryInterface $sections) {}

    public function index(): View
    {
        $orgProfile = Cache::rememberForever(OrgProfileService::CACHE_KEY, fn () => OrgProfile::query()
            ->with('media')
            ->first()
        );

        return view('frontend.about', [
            'orgProfile' => $orgProfile,
            'seo' => $this->seo()->forPage('about', [
                'title' => 'About Us',
                'description' => setting('organization.about_short'),
                'canonical' => route('about'),
                'breadcrumbs' => [['label' => 'Home', 'url' => route('home')], ['label' => 'About Us']],
                'schemas' => [StructuredData::webPage('AboutPage', 'About Us', route('about'), setting('organization.about_short')), StructuredData::organization()],
            ]),
            'sections' => $this->sections->activeForAboutPage(),
        ]);
    }
}
