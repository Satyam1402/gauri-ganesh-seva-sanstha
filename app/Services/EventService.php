<?php

namespace App\Services;

use App\Enums\EventStatus;
use App\Interfaces\EventRepositoryInterface;
use App\Models\Event;
use App\Repositories\EventRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class EventService
{
    public function __construct(private EventRepositoryInterface $events) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createEvent(array $data): Event
    {
        $event = $this->events->create($this->eventAttributes($data));

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $event->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        }

        $this->syncGallery($event, $data['gallery'] ?? [], []);
        $this->syncSeo($event, $data);

        $this->forgetCache();

        return $event->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateEvent(Event $event, array $data): Event
    {
        $this->events->update($event, $this->eventAttributes($data, $event));

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $event->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        } elseif (! empty($data['remove_featured_image'])) {
            $event->clearMediaCollection('featured_image');
        }

        $this->syncGallery($event, $data['gallery'] ?? [], $data['remove_gallery_ids'] ?? []);
        $this->syncSeo($event, $data);

        $this->forgetCache();

        return $event->refresh();
    }

    public function deleteEvent(Event $event): bool
    {
        $deleted = (bool) $event->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreEvent(Event $event): Event
    {
        $event->restore();
        $this->forgetCache();

        return $event;
    }

    public function toggleFeatured(Event $event): Event
    {
        $this->events->update($event, ['is_featured' => ! $event->is_featured]);
        $this->forgetCache();

        return $event;
    }

    public function publish(Event $event): Event
    {
        $this->events->update($event, ['status' => EventStatus::Published->value]);
        $this->forgetCache();

        return $event;
    }

    public function unpublish(Event $event): Event
    {
        $this->events->update($event, ['status' => EventStatus::Draft->value]);
        $this->forgetCache();

        return $event;
    }

    public function cancel(Event $event): Event
    {
        $this->events->update($event, ['status' => EventStatus::Cancelled->value]);
        $this->forgetCache();

        return $event;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->events->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkPublish(array $ids): int
    {
        $count = $this->events->bulkUpdateStatus($ids, EventStatus::Published->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * Public event caches (lists + upcoming/featured) must refresh whenever
     * an event changes.
     */
    public function forgetCache(): void
    {
        Cache::forget(EventRepository::CACHE_PREFIX.'.upcoming.3');
        Cache::forget(EventRepository::CACHE_PREFIX.'.upcoming.6');
        Cache::forget(EventRepository::CACHE_PREFIX.'.featured.3');
        Cache::forget(EventRepository::CACHE_PREFIX.'.featured.6');
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function eventAttributes(array $data, ?Event $event = null): array
    {
        return [
            'event_category_id' => $data['event_category_id'] ?? null,
            'title' => $data['title'],
            'slug' => $data['slug'] ?? $event?->slug,
            'short_description' => $data['short_description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'] ?? null,
            'start_time' => $data['start_time'] ?? null,
            'end_time' => $data['end_time'] ?? null,
            'venue' => $data['venue'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'map_url' => $data['map_url'] ?? null,
            'organizer' => $data['organizer'] ?? null,
            'max_participants' => $data['max_participants'] ?? null,
            'requires_registration' => (bool) ($data['requires_registration'] ?? false),
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
        ];
    }

    /**
     * @param  list<UploadedFile>  $newImages
     * @param  list<int>  $removeIds
     */
    private function syncGallery(Event $event, array $newImages, array $removeIds): void
    {
        foreach ($removeIds as $mediaId) {
            $event->getMedia('gallery')->firstWhere('id', $mediaId)?->delete();
        }

        foreach ($newImages as $image) {
            if ($image instanceof UploadedFile) {
                $event->addMedia($image)->toMediaCollection('gallery');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(Event $event, array $data): void
    {
        $seo = $event->seo()->firstOrNew();

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
            $media = $event->addMedia($data['og_image'])->toMediaCollection('og_image');
            $seo->og_image_media_id = $media->id;
        } elseif (! empty($data['remove_og_image'])) {
            $event->clearMediaCollection('og_image');
            $seo->og_image_media_id = null;
        }

        $event->seo()->save($seo);
    }
}
