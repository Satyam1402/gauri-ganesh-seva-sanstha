<?php

namespace App\Models;

use App\Enums\PartnerStatus;
use App\Traits\HasSlug;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Partner extends Model implements HasMedia
{
    use HasSlug, InteractsWithMedia, SoftDeletes;

    protected string $slugSource = 'name';

    protected $fillable = [
        'partner_type_id',
        'name',
        'slug',
        'short_description',
        'full_description',
        'website_url',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'country',
        'started_on',
        'ended_on',
        'logo_alt',
        'status',
        'is_featured',
        'display_order',
        'admin_notes',
        'created_by',
    ];

    /**
     * Private fields that must never leak through toArray()/JSON.
     */
    protected $hidden = [
        'admin_notes',
        'email',
        'phone',
        'address',
    ];

    protected function casts(): array
    {
        return [
            'status' => PartnerStatus::class,
            'is_featured' => 'boolean',
            'display_order' => 'integer',
            'started_on' => 'date',
            'ended_on' => 'date',
        ];
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(PartnerType::class, 'partner_type_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Everything the public website may show: active partners whose type
     * (if any) is itself active.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', PartnerStatus::Active->value)
            ->where(fn (Builder $q) => $q->whereNull('partner_type_id')
                ->orWhereHas('type', fn (Builder $t) => $t->where('is_active', true)));
    }

    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Default public ordering: admin display order first, then name.
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function isActive(): bool
    {
        return $this->status === PartnerStatus::Active;
    }

    /**
     * Accessible name for the logo image — falls back to the organisation
     * name so a logo never ships with empty alt text.
     */
    public function logoAlt(): string
    {
        return $this->logo_alt ?: $this->name.' logo';
    }

    /**
     * Hostname shown next to the website link ("example.org").
     */
    public function websiteHost(): ?string
    {
        if (! $this->website_url) {
            return null;
        }

        $host = parse_url($this->website_url, PHP_URL_HOST);

        return $host ? Str::replaceStart('www.', '', $host) : null;
    }

    /**
     * City, state and country joined into a single display line.
     */
    public function locationLine(): ?string
    {
        $parts = array_filter([$this->city, $this->state, $this->country]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * "Since 2021" or "2019 – 2023" for ended partnerships.
     */
    public function partnershipPeriod(): ?string
    {
        if ($this->started_on === null) {
            return null;
        }

        if ($this->ended_on === null) {
            return 'Since '.$this->started_on->format('Y');
        }

        return $this->started_on->format('Y').' – '.$this->ended_on->format('Y');
    }

    /**
     * Full description rendered from Markdown with raw HTML stripped and
     * unsafe links disallowed — the same safe mode the blog and FAQs use.
     */
    public function fullDescriptionHtml(): ?string
    {
        if (! $this->full_description) {
            return null;
        }

        return Str::markdown($this->full_description, [
            'html_input' => 'strip',
            'allow_unsafe_links' => false,
        ]);
    }

    /**
     * SVG is deliberately excluded: it can carry scripts and the media
     * library serves it as-is. Raster logos are converted to WebP anyway.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('logo')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('cover_image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);
    }

    /**
     * Conversions run synchronously (nonQueued) since partner images are
     * low-volume admin uploads — same trade-off as the other modules.
     * Logos are scaled to fit (never cropped) so wordmarks keep their
     * aspect ratio; the card reserves a fixed box to avoid layout shift.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(85)
            ->width(1200)
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(85)
            ->fit(Fit::Contain, 480, 240)
            ->nonQueued();
    }
}
