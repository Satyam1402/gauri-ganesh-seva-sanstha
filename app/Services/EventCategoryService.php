<?php

namespace App\Services;

use App\Interfaces\EventCategoryRepositoryInterface;
use App\Models\EventCategory;
use App\Repositories\EventCategoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class EventCategoryService
{
    public function __construct(private EventCategoryRepositoryInterface $categories) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): EventCategory
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
    public function updateCategory(EventCategory $category, array $data): EventCategory
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

    public function deleteCategory(EventCategory $category): bool
    {
        if ($category->events()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has events assigned to it and cannot be deleted.',
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
        Cache::forget(EventCategoryRepository::CACHE_KEY);
    }
}
