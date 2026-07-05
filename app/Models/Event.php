<?php

namespace App\Models;

use App\Enums\EventStatus;
use App\Enums\RegistrationStatus;
use App\Traits\HasSeo;
use App\Traits\HasSlug;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    use HasSeo, HasSlug, InteractsWithMedia, SoftDeletes;

    protected string $slugSource = 'title';

    protected $fillable = [
        'event_category_id',
        'title',
        'slug',
        'short_description',
        'full_description',
        'start_date',
        'end_date',
        'start_time',
        'end_time',
        'venue',
        'address',
        'city',
        'state',
        'map_url',
        'organizer',
        'max_participants',
        'requires_registration',
        'status',
        'is_featured',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'max_participants' => 'integer',
            'requires_registration' => 'boolean',
            'is_featured' => 'boolean',
            'status' => EventStatus::class,
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(EventCategory::class, 'event_category_id');
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }

    /**
     * Registrations that occupy a seat (pending / confirmed / attended) —
     * cancelled ones free their spot again.
     */
    public function activeRegistrations(): HasMany
    {
        return $this->registrations()->whereIn('status', RegistrationStatus::countedValues());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', EventStatus::Published->value);
    }

    /**
     * Everything the public site may show: published events plus completed
     * and cancelled ones (they stay browsable with a notice).
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->whereIn('status', EventStatus::publicValues());
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    public function scopeUpcoming(Builder $query): Builder
    {
        // Multi-day events stay "upcoming" until their last day passes.
        return $query->whereRaw('COALESCE(end_date, start_date) >= ?', [today()->toDateString()]);
    }

    public function scopePast(Builder $query): Builder
    {
        return $query->whereRaw('COALESCE(end_date, start_date) < ?', [today()->toDateString()]);
    }

    public function isUpcoming(): bool
    {
        return ($this->end_date ?? $this->start_date)->endOfDay()->isFuture();
    }

    public function isPast(): bool
    {
        return ! $this->isUpcoming();
    }

    /**
     * Registration is open only for upcoming, published events that require
     * it — and only while seats remain.
     */
    public function isRegistrationOpen(): bool
    {
        return $this->requires_registration
            && $this->status === EventStatus::Published
            && $this->isUpcoming()
            && ! $this->isFull();
    }

    public function isFull(): bool
    {
        return $this->max_participants !== null && $this->spotsLeft() <= 0;
    }

    /**
     * Remaining capacity, or null when the event is unlimited.
     */
    public function spotsLeft(): ?int
    {
        if ($this->max_participants === null) {
            return null;
        }

        $taken = $this->active_registrations_count ?? $this->activeRegistrations()->count();

        return max(0, $this->max_participants - $taken);
    }

    /**
     * "10 Aug 2026" or "10 – 12 Aug 2026" for multi-day events.
     */
    public function dateRange(): string
    {
        if ($this->end_date === null || $this->end_date->equalTo($this->start_date)) {
            return $this->start_date->format('d M Y');
        }

        return $this->start_date->format('d M').' – '.$this->end_date->format('d M Y');
    }

    /**
     * "10:00 AM – 1:00 PM", a lone start time, or null when untimed.
     */
    public function timeRange(): ?string
    {
        if ($this->start_time === null) {
            return null;
        }

        $range = Carbon::parse($this->start_time)->format('g:i A');

        if ($this->end_time !== null) {
            $range .= ' – '.Carbon::parse($this->end_time)->format('g:i A');
        }

        return $range;
    }

    /**
     * Venue, city and state joined into a single display line.
     */
    public function locationLine(): ?string
    {
        $parts = array_filter([$this->venue, $this->city, $this->state]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured_image')->singleFile();
        $this->addMediaCollection('gallery');
        $this->addMediaCollection('og_image')->singleFile();
    }

    /**
     * Conversions run synchronously (nonQueued) since event images are
     * low-volume CMS uploads — same trade-off as the other modules.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(80)
            ->width(640)
            ->nonQueued();
    }
}
