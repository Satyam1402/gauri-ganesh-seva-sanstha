<?php

namespace App\Models;

use App\Enums\CampaignStatus;
use App\Enums\PaymentStatus;
use App\Traits\HasSeo;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class DonationCampaign extends Model implements HasMedia
{
    use HasSeo, HasSlug, InteractsWithMedia, SoftDeletes;

    protected string $slugSource = 'name';

    protected $fillable = [
        'name',
        'slug',
        'short_description',
        'full_description',
        'goal_amount',
        'raised_amount',
        'currency',
        'status',
        'start_date',
        'end_date',
        'is_featured',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'goal_amount' => 'decimal:2',
            'raised_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_featured' => 'boolean',
            'status' => CampaignStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function donations(): HasMany
    {
        return $this->hasMany(Donation::class);
    }

    public function completedDonations(): HasMany
    {
        return $this->donations()->where('payment_status', PaymentStatus::Completed->value);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', CampaignStatus::Active->value);
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('order_column')->orderBy('name');
    }

    /**
     * Progress toward the goal, clamped to 0–100. Null when no goal is set.
     */
    public function progressPercent(): ?int
    {
        if ($this->goal_amount === null || (float) $this->goal_amount <= 0) {
            return null;
        }

        return (int) min(100, round(((float) $this->raised_amount / (float) $this->goal_amount) * 100));
    }

    /**
     * A campaign accepts donations while active and inside its date window.
     */
    public function isAcceptingDonations(): bool
    {
        if ($this->status !== CampaignStatus::Active) {
            return false;
        }

        if ($this->start_date && $this->start_date->isFuture()) {
            return false;
        }

        if ($this->end_date && $this->end_date->endOfDay()->isPast()) {
            return false;
        }

        return true;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('og_image')->singleFile();
    }

    /**
     * Conversions run synchronously (nonQueued) since campaign images are
     * low-volume CMS uploads — same trade-off as the Activities module.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->nonQueued();
    }
}
