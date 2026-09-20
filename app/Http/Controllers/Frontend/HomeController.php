<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\HomeSectionRepositoryInterface;
use App\Support\Seo\StructuredData;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __construct(private HomeSectionRepositoryInterface $sections) {}

    public function index(): View
    {
        return view('frontend.home', [
            'sections' => $this->sections->activeForHomepage(),
            'seo' => $this->seo()->forPage('home', [
                'canonical' => url('/'),
                'plain' => true,
                'schemas' => [StructuredData::organization(), StructuredData::website()],
            ]),
        ]);
    }
}
