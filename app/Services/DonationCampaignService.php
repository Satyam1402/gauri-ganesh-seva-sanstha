<?php

namespace App\Services;

use App\Enums\CampaignStatus;
use App\Interfaces\DonationCampaignRepositoryInterface;
use App\Models\DonationCampaign;
use App\Repositories\DonationCampaignRepository;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;

class DonationCampaignService
{
    public function __construct(
        private DonationCampaignRepositoryInterface $campaigns,
        private SeoService $seoService,
    ) {}

    /**
     * @param  array<string, mixed>  $data
     */
    public function createCampaign(array $data): DonationCampaign
    {
        $campaign = $this->campaigns->create([
            'name' => $data['name'],
            'slug' => $data['slug'] ?? null,
            'short_description' => $data['short_description'],
            'full_description' => $data['full_description'],
            'goal_amount' => $data['goal_amount'] ?? null,
            'currency' => $data['currency'] ?? config('donations.currency'),
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'order_column' => $data['order_column'] ?? 0,
        ]);

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $campaign->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        }

        $this->syncSeo($campaign, $data);
        $this->forgetCache();

        return $campaign->refresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateCampaign(DonationCampaign $campaign, array $data): DonationCampaign
    {
        $this->campaigns->update($campaign, [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? $campaign->slug,
            'short_description' => $data['short_description'],
            'full_description' => $data['full_description'],
            'goal_amount' => $data['goal_amount'] ?? null,
            'currency' => $data['currency'] ?? $campaign->currency,
            'status' => $data['status'],
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'order_column' => $data['order_column'] ?? $campaign->order_column,
        ]);

        if ($data['featured_image'] ?? null instanceof UploadedFile) {
            $campaign->addMedia($data['featured_image'])->toMediaCollection('featured_image');
        } elseif (! empty($data['remove_featured_image'])) {
            $campaign->clearMediaCollection('featured_image');
        }

        $this->syncSeo($campaign, $data);
        $this->forgetCache();

        return $campaign->refresh();
    }

    public function deleteCampaign(DonationCampaign $campaign): bool
    {
        $deleted = (bool) $campaign->delete();
        $this->forgetCache();

        return $deleted;
    }

    public function restoreCampaign(DonationCampaign $campaign): DonationCampaign
    {
        $campaign->restore();
        $this->forgetCache();

        return $campaign;
    }

    public function toggleFeatured(DonationCampaign $campaign): DonationCampaign
    {
        $this->campaigns->update($campaign, ['is_featured' => ! $campaign->is_featured]);
        $this->forgetCache();

        return $campaign;
    }

    public function activate(DonationCampaign $campaign): DonationCampaign
    {
        $this->campaigns->update($campaign, ['status' => CampaignStatus::Active->value]);
        $this->forgetCache();

        return $campaign;
    }

    public function archive(DonationCampaign $campaign): DonationCampaign
    {
        $this->campaigns->update($campaign, ['status' => CampaignStatus::Archived->value]);
        $this->forgetCache();

        return $campaign;
    }

    /**
     * @param  list<int>  $orderedIds
     */
    public function reorder(array $orderedIds): void
    {
        $this->campaigns->reorder($orderedIds);
        $this->forgetCache();
    }

    /**
     * Recompute the denormalised raised_amount from completed donations.
     */
    public function recalculateRaised(DonationCampaign $campaign): DonationCampaign
    {
        $campaign->forceFill([
            'raised_amount' => (float) $campaign->completedDonations()->sum('amount'),
        ])->save();

        $this->forgetCache();

        return $campaign;
    }

    /**
     * SEO fields are persisted by the shared SeoService (one write path
     * for every content type).
     *
     * @param  array<string, mixed>  $data
     */
    private function syncSeo(DonationCampaign $campaign, array $data): void
    {
        $this->seoService->sync($campaign, $data + ['schema_type' => 'WebPage']);
    }

    private function forgetCache(): void
    {
        Cache::forget(DonationCampaignRepository::CACHE_PREFIX.'.featured.3');
        Cache::forget(DonationCampaignRepository::CACHE_PREFIX.'.featured.6');
    }
}
