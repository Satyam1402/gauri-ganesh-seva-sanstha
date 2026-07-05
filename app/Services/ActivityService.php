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
    public function __construct(private ActivityRepositoryInterface $activities) {}

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
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(Activity $activity, array $data): void
    {
        $seo = $activity->seo()->firstOrNew();

        $seo->fill([
            'meta_title' => $data['meta_title'] ?? null,
            'meta_description' => $data['meta_description'] ?? null,
            'meta_keywords' => $data['meta_keywords'] ?? null,
            'canonical_url' => $data['canonical_url'] ?? null,
            'og_title' => $data['og_title'] ?? null,
            'og_description' => $data['og_description'] ?? null,
            'twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            'schema_type' => $data['schema_type'] ?? 'Event',
        ]);

        if ($data['og_image'] ?? null instanceof UploadedFile) {
            $media = $activity->addMedia($data['og_image'])->toMediaCollection('og_image');
            $seo->og_image_media_id = $media->id;
        } elseif (! empty($data['remove_og_image'])) {
            $activity->clearMediaCollection('og_image');
            $seo->og_image_media_id = null;
        }

        $activity->seo()->save($seo);
    }

    private function forgetCache(): void
    {
        Cache::forget(ActivityRepository::CACHE_PREFIX.'.latest.6');
        Cache::forget(ActivityRepository::CACHE_PREFIX.'.featured.6');
    }
}
