<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderFaqCategoriesRequest;
use App\Http\Requests\Admin\StoreFaqCategoryRequest;
use App\Http\Requests\Admin\UpdateFaqCategoryRequest;
use App\Interfaces\FaqCategoryRepositoryInterface;
use App\Models\FaqCategory;
use App\Services\FaqCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FaqCategoryController extends Controller
{
    public function __construct(
        private FaqCategoryRepositoryInterface $categories,
        private FaqCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', FaqCategory::class);

        return view('admin.faq-categories.index', [
            'categories' => $this->categories->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', FaqCategory::class);

        return view('admin.faq-categories.create');
    }

    public function store(StoreFaqCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', FaqCategory::class);

        $this->categoryService->createCategory($request->validated());

        return redirect()->route('admin.faq-categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(FaqCategory $faqCategory): View
    {
        $this->authorize('update', $faqCategory);

        return view('admin.faq-categories.edit', [
            'category' => $faqCategory,
        ]);
    }

    public function update(UpdateFaqCategoryRequest $request, FaqCategory $faqCategory): RedirectResponse
    {
        $this->authorize('update', $faqCategory);

        $this->categoryService->updateCategory($faqCategory, $request->validated());

        return redirect()->route('admin.faq-categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function toggle(FaqCategory $faqCategory): RedirectResponse
    {
        $this->authorize('update', $faqCategory);

        $this->categoryService->toggleActive($faqCategory);

        return back()->with('status', $faqCategory->is_active ? 'Category published.' : 'Category archived — its FAQs are hidden from the website.');
    }

    public function destroy(FaqCategory $faqCategory): RedirectResponse
    {
        $this->authorize('delete', $faqCategory);

        $this->categoryService->deleteCategory($faqCategory);

        return redirect()->route('admin.faq-categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    public function reorder(ReorderFaqCategoriesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', FaqCategory::class);

        $this->categoryService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
