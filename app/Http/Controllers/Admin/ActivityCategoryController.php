<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderActivityCategoriesRequest;
use App\Http\Requests\Admin\StoreActivityCategoryRequest;
use App\Http\Requests\Admin\UpdateActivityCategoryRequest;
use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Models\ActivityCategory;
use App\Services\ActivityCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ActivityCategoryController extends Controller
{
    public function __construct(
        private ActivityCategoryRepositoryInterface $categories,
        private ActivityCategoryService $activityCategoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', ActivityCategory::class);

        return view('admin.activity-categories.index', [
            'categories' => $this->categories->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', ActivityCategory::class);

        return view('admin.activity-categories.create');
    }

    public function store(StoreActivityCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', ActivityCategory::class);

        $this->activityCategoryService->createCategory($request->validated());

        return redirect()->route('admin.activity-categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(ActivityCategory $activityCategory): View
    {
        $this->authorize('update', $activityCategory);

        return view('admin.activity-categories.edit', [
            'category' => $activityCategory,
        ]);
    }

    public function update(UpdateActivityCategoryRequest $request, ActivityCategory $activityCategory): RedirectResponse
    {
        $this->authorize('update', $activityCategory);

        $this->activityCategoryService->updateCategory($activityCategory, $request->validated());

        return redirect()->route('admin.activity-categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(ActivityCategory $activityCategory): RedirectResponse
    {
        $this->authorize('delete', $activityCategory);

        $this->activityCategoryService->deleteCategory($activityCategory);

        return redirect()->route('admin.activity-categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    public function reorder(ReorderActivityCategoriesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ActivityCategory::class);

        $this->activityCategoryService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
