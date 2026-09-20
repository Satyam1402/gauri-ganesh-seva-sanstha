<?php

namespace App\Models;

use App\Enums\TestimonialStatus;
use App\Enums\TestimonialType;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Testimonial extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    public const MIN_RATING = 1;

    public const MAX_RATING = 5;

    protected $fillable = [
        'name',
        'designation',
        'organization',
        'location',
        'content',
        'rating',
        'type',
        'status',
        'is_featured',
        'display_order',
        'published_at',
        'testimonialable_type',
        'testimonialable_id',
        'consent_given',
        'consented_at',
        'admin_notes',
        'created_by',
    ];

    /**
     * Private fields that must never leak through toArray()/JSON.
     */
    protected $hidden = [
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'rating' => 'integer',
            'type' => TestimonialType::class,
            'status' => TestimonialStatus::class,
            'is_featured' => 'boolean',
            'display_order' => 'integer',
            'published_at' => 'datetime',
            'consent_given' => 'boolean',
            'consented_at' => 'datetime',
        ];
    }

    /**
     * The activity or campaign this testimonial is about, if any.
     */
    public function testimonialable(): MorphTo
    {
        return $this->morphTo();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Everything the public website may show: published, consented, and
     * past its publish date (a future date schedules it, like blog posts).
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', TestimonialStatus::Published->value)
            ->where('consent_given', true)
            ->where(fn (Builder $q) => $q->whereNull('published_at')->orWhere('published_at', '<=', now()));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeOfType(Builder $query, TestimonialType|string $type): Builder
    {
        return $query->where('type', $type instanceof TestimonialType ? $type->value : $type);
    }

    /**
     * Default public ordering: admin display order first, then newest.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderByDesc('published_at')->orderByDesc('id');
    }

    public function isPublished(): bool
    {
        return $this->status === TestimonialStatus::Published;
    }

    /**
     * A testimonial may only go live once the person has consented.
     */
    public function canBePublished(): bool
    {
        return $this->consent_given;
    }

    public function isScheduled(): bool
    {
        return $this->isPublished() && $this->published_at !== null && $this->published_at->isFuture();
    }

    /**
     * Designation, organisation and location joined into one attribution
     * line for cards, e.g. "Monthly Donor · Pune".
     */
    public function attributionLine(): ?string
    {
        $parts = array_filter([$this->designation, $this->organization, $this->location]);

        return $parts === [] ? null : implode(' · ', $parts);
    }

    /**
     * Label of the linked activity/campaign for the admin screens.
     */
    public function relatedLabel(): ?string
    {
        $related = $this->testimonialable;

        if ($related === null) {
            return null;
        }

        return match ($related::class) {
            Activity::class => 'Activity: '.$related->title,
            DonationCampaign::class => 'Campaign: '.$related->name,
            default => $related->name ?? $related->title ?? null,
        };
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Conversions run synchronously (nonQueued) since profile photos are
     * low-volume admin uploads — same trade-off as the other modules.
     * "avatar" is a square crop for cards; "webp" is a bounded full copy.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->width(800)
            ->nonQueued();

        $this->addMediaConversion('avatar')
            ->format('webp')
            ->quality(82)
            ->fit(Fit::Crop, 240, 240)
            ->nonQueued();
    }
}
