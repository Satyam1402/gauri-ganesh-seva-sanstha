<?php

namespace App\Services;

use App\Enums\PartnerStatus;
use App\Interfaces\PartnerRepositoryInterface;
use App\Models\Partner;
use App\Repositories\PartnerRepository;
use App\Repositories\PartnerTypeRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class PartnerService
{
    public function __construct(private PartnerRepositoryInterface $partners) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createPartner(array $data, ?int $createdBy = null): Partner
    {
        $partner = DB::transaction(function () use ($data, $createdBy) {
            $attributes = $this->attributes($data);
            $attributes['created_by'] = $createdBy;

            /** @var Partner $partner */
            $partner = $this->partners->create($attributes);

            $this->syncImages($partner, $data);

            return $partner;
        });

        $this->forgetCache();

        return $partner->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePartner(Partner $partner, array $data): Partner
    {
        DB::transaction(function () use ($partner, $data) {
            $this->partners->update($partner, $this->attributes($data, $partner));
            $this->syncImages($partner, $data);
        });

        $this->forgetCache();

        return $partner->refresh();
    }

    public function deletePartner(Partner $partner): bool
    {
        $deleted = (bool) $partner->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restorePartner(Partner $partner): Partner
    {
        $partner->restore();
        $this->forgetCache();

        return $partner;
    }

    public function toggleFeatured(Partner $partner): Partner
    {
        $this->partners->update($partner, ['is_featured' => ! $partner->is_featured]);
        $this->forgetCache();

        return $partner;
    }

    public function updateDisplayOrder(Partner $partner, int $order): Partner
    {
        $this->partners->update($partner, ['display_order' => $order]);
        $this->forgetCache();

        return $partner;
    }

    public function activate(Partner $partner): Partner
    {
        return $this->setStatus($partner, PartnerStatus::Active);
    }

    public function deactivate(Partner $partner): Partner
    {
        return $this->setStatus($partner, PartnerStatus::Inactive);
    }

    public function archive(Partner $partner): Partner
    {
        return $this->setStatus($partner, PartnerStatus::Archived);
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkDelete(array $ids): int
    {
        $count = $this->partners->bulkDelete($ids);
        $this->forgetCache();

        return $count;
    }

    /**
     * @param  list<int>  $ids
     */
    public function bulkSetStatus(array $ids, PartnerStatus $status): int
    {
        $count = $this->partners->bulkUpdateStatus($ids, $status->value);
        $this->forgetCache();

        return $count;
    }

    /**
     * Invalidate every public partner cache (section lists, the public
     * page, type tab counts) by bumping the shared version number.
     */
    public function forgetCache(): void
    {
        $version = (int) Cache::get(PartnerRepository::CACHE_VERSION_KEY, 1);
        Cache::forever(PartnerRepository::CACHE_VERSION_KEY, $version + 1);
        Cache::forget(PartnerTypeRepository::CACHE_KEY);
    }

    private function setStatus(Partner $partner, PartnerStatus $status): Partner
    {
        $this->partners->update($partner, ['status' => $status->value]);
        $this->forgetCache();

        return $partner;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function attributes(array $data, ?Partner $partner = null): array
    {
        return [
            'partner_type_id' => ($data['partner_type_id'] ?? null) ?: null,
            'name' => $data['name'],
            'slug' => ($data['slug'] ?? null) ?: $partner?->slug,
            'short_description' => $data['short_description'] ?? null,
            'full_description' => $data['full_description'] ?? null,
            'website_url' => $data['website_url'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'started_on' => $data['started_on'] ?? null,
            'ended_on' => $data['ended_on'] ?? null,
            'logo_alt' => $data['logo_alt'] ?? null,
            'status' => $data['status'],
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'display_order' => (int) ($data['display_order'] ?? 0),
            'admin_notes' => $data['admin_notes'] ?? null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncImages(Partner $partner, array $data): void
    {
        foreach (['logo' => 'remove_logo', 'cover_image' => 'remove_cover_image'] as $collection => $removeFlag) {
            if (($data[$collection] ?? null) instanceof UploadedFile) {
                // singleFile collections replace the previous logo automatically.
                $partner->addMedia($data[$collection])->toMediaCollection($collection);
            } elseif (! empty($data[$removeFlag])) {
                $partner->clearMediaCollection($collection);
            }
        }
    }
}
