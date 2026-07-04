<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderHomeSectionsRequest;
use App\Http\Requests\Admin\UpdateHomeSectionRequest;
use App\Interfaces\HomeSectionRepositoryInterface;
use App\Models\HomeSection;
use App\Services\HomeSectionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class HomeSectionController extends Controller
{
    public function __construct(
        private HomeSectionRepositoryInterface $sections,
        private HomeSectionService $homeSectionService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', HomeSection::class);

        return view('admin.home-sections.index', [
            'sections' => $this->sections->allOrdered(),
        ]);
    }

    public function edit(HomeSection $homeSection): View
    {
        $this->authorize('update', $homeSection);

        return view('admin.home-sections.edit', [
            'section' => $homeSection->load(['buttons', 'items.media', 'media']),
        ]);
    }

    public function update(UpdateHomeSectionRequest $request, HomeSection $homeSection): RedirectResponse
    {
        $this->authorize('update', $homeSection);

        $this->homeSectionService->updateSection($homeSection, $request->validated());

        return redirect()->route('admin.home-sections.edit', $homeSection)
            ->with('status', 'Section updated successfully.');
    }

    public function toggle(HomeSection $homeSection): RedirectResponse
    {
        $this->authorize('update', $homeSection);

        $this->homeSectionService->toggle($homeSection);

        return redirect()->route('admin.home-sections.index')
            ->with('status', "\"{$homeSection->name}\" is now ".($homeSection->is_active ? 'enabled' : 'disabled').'.');
    }

    public function reorder(ReorderHomeSectionsRequest $request): JsonResponse
    {
        $this->authorize('viewAny', HomeSection::class);

        $this->homeSectionService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
