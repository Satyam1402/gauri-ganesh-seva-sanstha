<?php

namespace App\Services;

use App\Interfaces\FaqCategoryRepositoryInterface;
use App\Models\FaqCategory;
use Illuminate\Validation\ValidationException;

class FaqCategoryService
{
    public function __construct(
        private FaqCategoryRepositoryInterface $categories,
        private FaqService $faqService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): FaqCategory
    {
        $category = $this->categories->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? true),
        ]);

        $this->forgetCache();

        return $category;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCategory(FaqCategory $category, array $data): FaqCategory
    {
        $this->categories->update($category, [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $category->slug,
            'description' => $data['description'] ?? null,
            'is_active' => (bool) ($data['is_active'] ?? false),
        ]);

        $this->forgetCache();

        return $category->refresh();
    }

    /**
     * Archive (deactivate) or re-publish a category. Archiving hides the
     * category and all of its FAQs from the public site.
     */
    public function toggleActive(FaqCategory $category): FaqCategory
    {
        $this->categories->update($category, ['is_active' => ! $category->is_active]);
        $this->forgetCache();

        return $category;
    }

    public function deleteCategory(FaqCategory $category): bool
    {
        if ($category->faqs()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has FAQs assigned to it and cannot be deleted. Archive it instead, or move its FAQs first.',
            ]);
        }

        $deleted = $this->categories->delete($category);
        $this->forgetCache();

        return $deleted;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->categories->reorder($orderedIds);
        $this->forgetCache();
    }

    /**
     * Category changes affect grouping, tab counts and visibility, so the
     * FAQ caches are busted too (FaqService clears the category cache).
     */
    private function forgetCache(): void
    {
        $this->faqService->forgetCache();
    }
}
