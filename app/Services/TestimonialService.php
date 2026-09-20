<?php

namespace App\Services;

use App\Enums\TestimonialStatus;
use App\Interfaces\TestimonialRepositoryInterface;
use App\Models\Activity;
use App\Models\DonationCampaign;
use App\Models\Testimonial;
use App\Repositories\TestimonialRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class TestimonialService
{
    /**
     * Models a testimonial may be linked to, keyed by the prefix used in
     * the admin form's "related to" select ("activity:12", "campaign:3").
     *
     * @var array<string, class-string>
     */
    public const RELATED_TYPES = [
        'activity' => Activity::class,
        'campaign' => DonationCampaign::class,
    ];

    public function __construct(private TestimonialRepositoryInterface $testimonials) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createTestimonial(array $data, ?int $createdBy = null): Testimonial
    {
        $testimonial = DB::transaction(function () use ($data, $createdBy) {
            $attributes = $this->attributes($data);
            $attributes['created_by'] = $createdBy;

            /** @var Testimonial $testimonial */
            $testimonial = $this->testimonials->create($attributes);

            $this->syncPhoto($testimonial, $data);

            return $testimonial;
        });

        $this->forgetCache();

        return $testimonial->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateTestimonial(Testimonial $testimonial, array $data): Testimonial
    {
        DB::transaction(function () use ($testimonial, $data) {
            $this->testimonials->update($testimonial, $this->attributes($data, $testimonial));
            $this->syncPhoto($testimonial, $data);
        });

        $this->forgetCache();

        return $testimonial->refresh();
    }

    public function deleteTestimonial(Testimonial $testimonial): bool
    {
        $deleted = (bool) $testimonial->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreTestimonial(Testimonial $testimonial): Testimonial
    {
        $testimonial->restore();
        $this->forgetCache();

        return $testimonial;
    }

    public function toggleFeatured(Testimonial $testimonial): Testimonial
    {
        $this->testimonials->update($testimonial, ['is_featured' => ! $testimonial->is_featured]);
        $this->forgetCache();

        return $testimonial;
    }

    public function updateDisplayOrder(Testimonial $testimonial, int $order): Testimonial
    {
        $this->testimonials->update($testimonial, ['display_order' => $order]);
        $this->forgetCache();

        return $testimonial;
    }

    /**
     * Publish a testimonial. Refuses without consent — the caller should
     * check canBePublished() first and surface a friendly message.
     *
     * @throws InvalidArgumentException
     */
    public function publish(Testimonial $testimonial): Testimonial
    {
        if (! $testimonial->canBePublished()) {
            throw new InvalidArgumentException('A testimonial cannot be published until consent has been recorded.');
        }

        $this->testimonials->update($testimonial, [
            'status' => TestimonialStatus::Published->value,
            // Keep an existing (possibly scheduled) date; only fill it when empty.
            'published_at' => $testimonial->published_at ?? now(),
        ]);
        $this->forgetCache();

        return $testimonial;
    }

    public function unpublish(Testimonial $testimonial): Testimonial
    {
        $this->testimonials->update($testimonial, ['status' => TestimonialStatus::Unpublished->value]);
        $this->forgetCache();

        return $testimonial;
    }

    public function archive(Testimonial $testimonial): Testimonial
    {
        $this->testimonials->update($testimonial, ['status' => TestimonialStatus::Archived->value]);
        $this->forgetCache();

        return $testimonial;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->testimonials->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * Bulk publish only the selected testimonials that have consent; the
     * rest are left untouched and reported back via the returned count.
     *
     * @param  list<int>  $ids
     * @return array{published: int, skipped: int}
     */
    public function bulkPublish(array $ids): array
    {
        $consented = Testimonial::query()
            ->whereIn('id', $ids)
            ->where('consent_given', true)
            ->pluck('id')
            ->all();

        $published = 0;

        if ($consented !== []) {
            // Fill published_at only where it's empty so scheduled dates survive.
            Testimonial::query()->whereIn('id', $consented)->whereNull('published_at')->update(['published_at' => now()]);
            $published = $this->testimonials->bulkUpdateStatus($consented, TestimonialStatus::Published->value);
        }

        $this->forgetCache();

        return ['published' => $published, 'skipped' => count($ids) - count($consented)];
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkUnpublish(array $ids): int
    {
        $count = $this->testimonials->bulkUpdateStatus($ids, TestimonialStatus::Unpublished->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkArchive(array $ids): int
    {
        $count = $this->testimonials->bulkUpdateStatus($ids, TestimonialStatus::Archived->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * Invalidate every public testimonial cache (section lists, the public
     * page, related lists) by bumping the shared version number.
     */
    public function forgetCache(): void
    {
        $version = (int) Cache::get(TestimonialRepository::CACHE_VERSION_KEY, 1);
        Cache::forever(TestimonialRepository::CACHE_VERSION_KEY, $version + 1);
    }

    /**
     * Options for the admin "related to" select: published activities and
     * active/completed campaigns, prefixed so one select covers both.
     *
     * @return array<string, string>
     */
    public function relatedOptions(): array
    {
        $activities = Activity::query()->orderBy('title')->pluck('title', 'id')
            ->mapWithKeys(fn (string $title, int $id) => ["activity:{$id}" => "Activity: {$title}"]);

        $campaigns = DonationCampaign::query()->orderBy('name')->pluck('name', 'id')
            ->mapWithKeys(fn (string $name, int $id) => ["campaign:{$id}" => "Campaign: {$name}"]);

        return ['' => 'Not linked'] + $activities->all() + $campaigns->all();
    }

    /**
     * Encode a testimonial's morph link back into the select's value form.
     */
    public static function relatedValue(?Testimonial $testimonial): string
    {
        if ($testimonial?->testimonialable_type === null) {
            return '';
        }

        $prefix = array_search($testimonial->testimonialable_type, self::RELATED_TYPES, true);

        return $prefix === false ? '' : "{$prefix}:{$testimonial->testimonialable_id}";
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Testimonial $testimonial = null): array
    {
        $consentGiven = (bool) ($data['consent_given'] ?? false);
        $status = $data['status'];

        // Never let a form submission publish without consent — downgrade
        // to pending review so the record is kept but stays private.
        if ($status === TestimonialStatus::Published->value && ! $consentGiven) {
            $status = TestimonialStatus::PendingReview->value;
        }

        $publishedAt = ! empty($data['published_at']) ? $data['published_at'] : $testimonial?->published_at;

        if ($status === TestimonialStatus::Published->value && $publishedAt === null) {
            $publishedAt = now();
        }

        [$relatedType, $relatedId] = $this->parseRelated($data['related'] ?? null);

        return [
            'name' => $data['name'],
            'designation' => $data['designation'] ?? null,
            'organization' => $data['organization'] ?? null,
            'location' => $data['location'] ?? null,
            'content' => $data['content'],
            'rating' => $data['rating'] ?? null,
            'type' => $data['type'],
            'status' => $status,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'published_at' => $publishedAt,
            'testimonialable_type' => $relatedType,
            'testimonialable_id' => $relatedId,
            'consent_given' => $consentGiven,
            'consented_at' => $consentGiven
                ? ($data['consented_at'] ?? null ?: ($testimonial?->consented_at ?? now()))
                : null,
            'admin_notes' => $data['admin_notes'] ?? null,
        ];
    }

    /**
     * "activity:12" → [Activity::class, 12]; anything else → [null, null].
     *
     * @return array{0: ?string, 1: ?int}
     */
    private function parseRelated(?string $value): array
    {
        if (empty($value) || ! str_contains($value, ':')) {
            return [null, null];
        }

        [$prefix, $id] = explode(':', $value, 2);
        $class = self::RELATED_TYPES[$prefix] ?? null;

        if ($class === null || ! ctype_digit($id)) {
            return [null, null];
        }

        return [(new $class)->getMorphClass(), (int) $id];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncPhoto(Testimonial $testimonial, array $data): void
    {
        if (($data['profile_photo'] ?? null) instanceof UploadedFile) {
            $testimonial->addMedia($data['profile_photo'])->toMediaCollection('profile_photo');
        } elseif (! empty($data['remove_profile_photo'])) {
            $testimonial->clearMediaCollection('profile_photo');
        }
    }
}
