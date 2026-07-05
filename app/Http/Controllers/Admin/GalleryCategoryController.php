<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderGalleryCategoriesRequest;
use App\Http\Requests\Admin\StoreGalleryCategoryRequest;
use App\Http\Requests\Admin\UpdateGalleryCategoryRequest;
use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Models\GalleryCategory;
use App\Services\GalleryCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GalleryCategoryController extends Controller
{
    public function __construct(
        private GalleryCategoryRepositoryInterface $categories,
        private GalleryCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', GalleryCategory::class);

        return view('admin.gallery-categories.index', [
            'categories' => $this->categories->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', GalleryCategory::class);

        return view('admin.gallery-categories.create');
    }

    public function store(StoreGalleryCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', GalleryCategory::class);

        $this->categoryService->createCategory($request->validated());

        return redirect()->route('admin.gallery-categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(GalleryCategory $galleryCategory): View
    {
        $this->authorize('update', $galleryCategory);

        return view('admin.gallery-categories.edit', [
            'category' => $galleryCategory,
        ]);
    }

    public function update(UpdateGalleryCategoryRequest $request, GalleryCategory $galleryCategory): RedirectResponse
    {
        $this->authorize('update', $galleryCategory);

        $this->categoryService->updateCategory($galleryCategory, $request->validated());

        return redirect()->route('admin.gallery-categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(GalleryCategory $galleryCategory): RedirectResponse
    {
        $this->authorize('delete', $galleryCategory);

        $this->categoryService->deleteCategory($galleryCategory);

        return redirect()->route('admin.gallery-categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    public function reorder(ReorderGalleryCategoriesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', GalleryCategory::class);

        $this->categoryService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
