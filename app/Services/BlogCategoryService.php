<?php

namespace App\Services;

use App\Interfaces\BlogCategoryRepositoryInterface;
use App\Models\BlogCategory;
use App\Repositories\BlogCategoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class BlogCategoryService
{
    public function __construct(private BlogCategoryRepositoryInterface $categories) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): BlogCategory
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
    public function updateCategory(BlogCategory $category, array $data): BlogCategory
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

    public function deleteCategory(BlogCategory $category): bool
    {
        if ($category->posts()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has posts assigned to it and cannot be deleted.',
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

    private function forgetCache(): void
    {
        Cache::forget(BlogCategoryRepository::CACHE_KEY);
    }
}
