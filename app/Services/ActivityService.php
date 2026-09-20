<?php

namespace App\Services;

use App\Enums\ActivityStatus;
use App\Interfaces\ActivityRepositoryInterface;
use App\Models\Activity;
use App\Repositories\ActivityRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class ActivityService
{
    public function __construct(
        private ActivityRepositoryInterface $activities,
        private SeoService $seoService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createActivity(array $data): Activity
    {
        $activity = $this->activities->create([
            'activity_category_id' => $data['activity_category_id'],
            'title' => $data['title'],
            'slug' => $data['slug'] ?? null,
            'short_description' => $data['short_description'],
            'full_description' => $data['full_description'],
            'activity_date' => $data['activity_date'],
            'location' => $data['location'] ?? null,
            'organizer' => $data['organizer'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ]);

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $activity->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        }

        $this->syncGallery($activity, $data['gallery'] ?? [], []);
        $this->syncSeo($activity, $data);

        $this->forgetCache();

        return $activity->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateActivity(Activity $activity, array $data): Activity
    {
        $this->activities->update($activity, [
            'activity_category_id' => $data['activity_category_id'],
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $activity->slug,
            'short_description' => $data['short_description'],
            'full_description' => $data['full_description'],
            'activity_date' => $data['activity_date'],
            'location' => $data['location'] ?? null,
            'organizer' => $data['organizer'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ]);

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $activity->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        } elseif (! empty($data['remove_featured_image'])) {
            $activity->clearMediaCollection('featured_image');
        }

        $this->syncGallery($activity, $data['gallery'] ?? [], $data['remove_gallery_ids'] ?? []);
        $this->syncSeo($activity, $data);

        $this->forgetCache();

        return $activity->refresh();
    }

    public function deleteActivity(Activity $activity): bool
    {
        $deleted = (bool) $activity->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreActivity(Activity $activity): Activity
    {
        $activity->restore();
        $this->forgetCache();

        return $activity;
    }

    public function toggleFeatured(Activity $activity): Activity
    {
        $this->activities->update($activity, ['is_featured' => ! $activity->is_featured]);
        $this->forgetCache();

        return $activity;
    }

    public function publish(Activity $activity): Activity
    {
        $this->activities->update($activity, ['status' => ActivityStatus::Published->value]);
        $this->forgetCache();

        return $activity;
    }

    public function unpublish(Activity $activity): Activity
    {
        $this->activities->update($activity, ['status' => ActivityStatus::Draft->value]);
        $this->forgetCache();

        return $activity;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->activities->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkPublish(array $ids): int
    {
        $count = $this->activities->bulkUpdateStatus($ids, ActivityStatus::Published->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkUpdateCategory(array $ids, int $categoryId): int
    {
        $count = $this->activities->bulkUpdateCategory($ids, $categoryId);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeIds
     */
    private function syncGallery(Activity $activity, array $newImages, array $removeIds): void
    {
        foreach ($removeIds as $mediaId) {
            $activity->getMedia('gallery')->firstWhere('id', $mediaId)?->delete();
        }

        foreach ($newImages as $image) {
            if ($image instanceof UploadedFile) {
                $activity->addMedia($image)->toMediaCollection('gallery');
            }
        }
    }

    /**
     * SEO fields are persisted by the shared SeoService (one write path
     * for every content type).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(Activity $activity, array $data): void
    {
        $this->seoService->sync($activity, $data + ['schema_type' => 'Article']);
    }

    private function forgetCache(): void
    {
        Cache::forget(ActivityRepository::CACHE_PREFIX.'.latest.6');
        Cache::forget(ActivityRepository::CACHE_PREFIX.'.featured.6');
    }
}
