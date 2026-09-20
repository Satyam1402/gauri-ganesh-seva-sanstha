<?php

namespace App\Services;

use App\Enums\FaqStatus;
use App\Interfaces\FaqRepositoryInterface;
use App\Models\Faq;
use App\Repositories\FaqCategoryRepository;
use App\Repositories\FaqRepository;
use Illuminate\Support\Facades\Cache;

class FaqService
{
    public function __construct(private FaqRepositoryInterface $faqs) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFaq(array $data, ?int $createdBy = null): Faq
    {
        $attributes = $this->attributes($data);
        $attributes['created_by'] = $createdBy;

        /** @var Faq $faq */
        $faq = $this->faqs->create($attributes);

        $this->forgetCache();

        return $faq;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateFaq(Faq $faq, array $data): Faq
    {
        $this->faqs->update($faq, $this->attributes($data, $faq));
        $this->forgetCache();

        return $faq->refresh();
    }

    public function deleteFaq(Faq $faq): bool
    {
        $deleted = (bool) $faq->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreFaq(Faq $faq): Faq
    {
        $faq->restore();
        $this->forgetCache();

        return $faq;
    }

    public function toggleFeatured(Faq $faq): Faq
    {
        $this->faqs->update($faq, ['is_featured' => ! $faq->is_featured]);
        $this->forgetCache();

        return $faq;
    }

    public function updateDisplayOrder(Faq $faq, int $order): Faq
    {
        $this->faqs->update($faq, ['display_order' => $order]);
        $this->forgetCache();

        return $faq;
    }

    public function publish(Faq $faq): Faq
    {
        $this->faqs->update($faq, [
            'status' => FaqStatus::Published->value,
            // Keep an existing (possibly scheduled) date; only fill it when empty.
            'published_at' => $faq->published_at ?? now(),
        ]);
        $this->forgetCache();

        return $faq;
    }

    public function unpublish(Faq $faq): Faq
    {
        $this->faqs->update($faq, ['status' => FaqStatus::Unpublished->value]);
        $this->forgetCache();

        return $faq;
    }

    public function archive(Faq $faq): Faq
    {
        $this->faqs->update($faq, ['status' => FaqStatus::Archived->value]);
        $this->forgetCache();

        return $faq;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->faqs->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkPublish(array $ids): int
    {
        // Fill published_at only where it's empty so scheduled dates survive.
        Faq::query()->whereIn('id', $ids)->whereNull('published_at')->update(['published_at' => now()]);
        $count = $this->faqs->bulkUpdateStatus($ids, FaqStatus::Published->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkUnpublish(array $ids): int
    {
        $count = $this->faqs->bulkUpdateStatus($ids, FaqStatus::Unpublished->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkArchive(array $ids): int
    {
        $count = $this->faqs->bulkUpdateStatus($ids, FaqStatus::Archived->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkAssignCategory(array $ids, ?int $categoryId): int
    {
        $count = Faq::query()->whereIn('id', $ids)->update(['faq_category_id' => $categoryId]);
        $this->forgetCache();

        return $count;
    }

    /**
     * Invalidate every public FAQ cache (section lists, the public page,
     * category tab counts) by bumping the shared version number.
     */
    public function forgetCache(): void
    {
        $version = (int) Cache::get(FaqRepository::CACHE_VERSION_KEY, 1);
        Cache::forever(FaqRepository::CACHE_VERSION_KEY, $version + 1);
        Cache::forget(FaqCategoryRepository::CACHE_KEY);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Faq $faq = null): array
    {
        $status = $data['status'];
        $publishedAt = ! empty($data['published_at']) ? $data['published_at'] : $faq?->published_at;

        if ($status === FaqStatus::Published->value && $publishedAt === null) {
            $publishedAt = now();
        }

        return [
            'faq_category_id' => ($data['faq_category_id'] ?? null) ?: null,
            'question' => $data['question'],
            'answer' => $data['answer'],
            'status' => $status,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'published_at' => $publishedAt,
            'admin_notes' => $data['admin_notes'] ?? null,
        ];
    }
}
