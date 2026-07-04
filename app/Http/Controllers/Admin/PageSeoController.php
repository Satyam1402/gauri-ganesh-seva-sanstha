<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateSeoRequest;
use App\Models\Page;
use App\Services\SeoService;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PageSeoController extends Controller
{
    public function __construct(private SeoService $seoService) {}

    public function edit(Page $page): View
    {
        $this->authorize('update', $page);

        return view('admin.pages.seo', [
            'page' => $page->load('seo'),
        ]);
    }

    public function update(UpdateSeoRequest $request, Page $page): RedirectResponse
    {
        $this->authorize('update', $page);

        $this->seoService->updateSeo($page, $request->validated());

        return redirect()->route('admin.pages.seo.edit', $page)
            ->with('status', 'SEO settings updated successfully.');
    }
}
