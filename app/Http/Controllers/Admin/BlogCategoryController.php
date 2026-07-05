<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderBlogCategoriesRequest;
use App\Http\Requests\Admin\StoreBlogCategoryRequest;
use App\Http\Requests\Admin\UpdateBlogCategoryRequest;
use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Models\BlogCategory;
use App\Services\BlogCategoryService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class BlogCategoryController extends Controller
{
    public function __construct(
        private BlogCategoryRepositoryInterface $categories,
        private BlogCategoryService $categoryService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', BlogCategory::class);

        return view('admin.blog-categories.index', [
            'categories' => $this->categories->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', BlogCategory::class);

        return view('admin.blog-categories.create');
    }

    public function store(StoreBlogCategoryRequest $request): RedirectResponse
    {
        $this->authorize('create', BlogCategory::class);

        $this->categoryService->createCategory($request->validated());

        return redirect()->route('admin.blog-categories.index')
            ->with('status', 'Category created successfully.');
    }

    public function edit(BlogCategory $blogCategory): View
    {
        $this->authorize('update', $blogCategory);

        return view('admin.blog-categories.edit', [
            'category' => $blogCategory,
        ]);
    }

    public function update(UpdateBlogCategoryRequest $request, BlogCategory $blogCategory): RedirectResponse
    {
        $this->authorize('update', $blogCategory);

        $this->categoryService->updateCategory($blogCategory, $request->validated());

        return redirect()->route('admin.blog-categories.index')
            ->with('status', 'Category updated successfully.');
    }

    public function destroy(BlogCategory $blogCategory): RedirectResponse
    {
        $this->authorize('delete', $blogCategory);

        $this->categoryService->deleteCategory($blogCategory);

        return redirect()->route('admin.blog-categories.index')
            ->with('status', 'Category deleted successfully.');
    }

    public function reorder(ReorderBlogCategoriesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', BlogCategory::class);

        $this->categoryService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
