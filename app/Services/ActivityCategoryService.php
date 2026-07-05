<?php

namespace App\Services;

use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Models\ActivityCategory;
use App\Repositories\ActivityCategoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class ActivityCategoryService
{
    public function __construct(private ActivityCategoryRepositoryInterface $categories) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): ActivityCategory
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
    public function updateCategory(ActivityCategory $category, array $data): ActivityCategory
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

    public function deleteCategory(ActivityCategory $category): bool
    {
        if ($category->activities()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has activities assigned to it and cannot be deleted.',
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
        Cache::forget(ActivityCategoryRepository::CACHE_KEY);
    }
}
