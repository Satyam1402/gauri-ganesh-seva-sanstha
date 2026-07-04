<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\HomeSectionRepositoryInterface;
use App\Models\Page;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private HomeSectionRepositoryInterface $sections) {}

    public function index(): View
    {
        $page = Cache::rememberForever('pages.home', fn () => Page::query()
            ->where('slug', 'home')
            ->with(['seo.ogImage', 'media'])
            ->first()
        );

        return view('frontend.home', [
            'page' => $page,
            'sections' => $this->sections->activeForHomepage(),
        ]);
    }
}
