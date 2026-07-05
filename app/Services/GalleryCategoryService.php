<?php

namespace App\Services;

use App\Interfaces\GalleryCategoryRepositoryInterface;
use App\Models\GalleryCategory;
use App\Repositories\GalleryCategoryRepository;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\ValidationException;

class GalleryCategoryService
{
    public function __construct(private GalleryCategoryRepositoryInterface $categories) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCategory(array $data): GalleryCategory
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
    public function updateCategory(GalleryCategory $category, array $data): GalleryCategory
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

    public function deleteCategory(GalleryCategory $category): bool
    {
        if ($category->albums()->withTrashed()->exists()) {
            throw ValidationException::withMessages([
                'category' => 'This category has albums assigned to it and cannot be deleted.',
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
        Cache::forget(GalleryCategoryRepository::CACHE_KEY);
    }
}
