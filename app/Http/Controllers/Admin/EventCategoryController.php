<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderEventCategoriesRequest;
use App\Http\Requests\Admin\StoreEventCategoryRequest;
use App\Http\Requests\Admin\UpdateEventCategoryRequest;
use App\Interfaces\EventCategoryRepositoryInterface;
use App\Models\EventCategory;
use App\Services\EventCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class EventCategoryController extends Controller
{
    public function __construct(
        private EventCategoryRepositoryInterface $categories,
        private EventCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', EventCategory::class);

        return view('admin.event-categories.index', [
            'categories' => $this->categories->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', EventCategory::class);

        return view('admin.event-categories.create');
    }

    public function store(StoreEventCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', EventCategory::class);

        $this->categoryService->createCategory($request->validated());

        return redirect()->route('admin.event-categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(EventCategory $eventCategory): View
    {
        $this->authorize('update', $eventCategory);

        return view('admin.event-categories.edit', [
            'category' => $eventCategory,
        ]);
    }

    public function update(UpdateEventCategoryRequest $request, EventCategory $eventCategory): RedirectResponse
    {
        $this->authorize('update', $eventCategory);

        $this->categoryService->updateCategory($eventCategory, $request->validated());

        return redirect()->route('admin.event-categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(EventCategory $eventCategory): RedirectResponse
    {
        $this->authorize('delete', $eventCategory);

        $this->categoryService->deleteCategory($eventCategory);

        return redirect()->route('admin.event-categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    public function reorder(ReorderEventCategoriesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', EventCategory::class);

        $this->categoryService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
