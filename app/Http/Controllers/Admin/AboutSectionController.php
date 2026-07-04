<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderAboutSectionsRequest;
use App\Http\Requests\Admin\UpdateAboutSectionRequest;
use App\Interfaces\AboutSectionRepositoryInterface;
use App\Models\AboutSection;
use App\Services\AboutSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class AboutSectionController extends Controller
{
    public function __construct(
        private AboutSectionRepositoryInterface $sections,
        private AboutSectionService $aboutSectionService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', AboutSection::class);

        return view('admin.about-sections.index', [
            'sections' => $this->sections->allOrdered(),
        ]);
    }

    public function edit(AboutSection $aboutSection): View
    {
        $this->authorize('update', $aboutSection);

        return view('admin.about-sections.edit', [
            'section' => $aboutSection->load(['buttons', 'items.media', 'media']),
        ]);
    }

    public function update(UpdateAboutSectionRequest $request, AboutSection $aboutSection): RedirectResponse
    {
        $this->authorize('update', $aboutSection);

        $this->aboutSectionService->updateSection($aboutSection, $request->validated());

        return redirect()->route('admin.about-sections.edit', $aboutSection)
            ->with('status', 'Section updated successfully.');
    }

    public function toggle(AboutSection $aboutSection): RedirectResponse
    {
        $this->authorize('update', $aboutSection);

        $this->aboutSectionService->toggle($aboutSection);

        return redirect()->route('admin.about-sections.index')
            ->with('status', "\"{$aboutSection->name}\" is now ".($aboutSection->is_active ? 'enabled' : 'disabled').'.');
    }

    public function reorder(ReorderAboutSectionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', AboutSection::class);

        $this->aboutSectionService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
