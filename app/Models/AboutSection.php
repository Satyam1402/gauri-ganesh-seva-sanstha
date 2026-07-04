<?php

namespace App\Models;

use App\Enums\AboutSectionKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AboutSection extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'key',
        'name',
        'heading',
        'subheading',
        'description',
        'is_active',
        'order_column',
        'settings',
    ];

    protected function casts(): array
    {
        return [
            'key' => AboutSectionKey::class,
            'is_active' => 'boolean',
            'settings' => 'array',
        ];
    }

    public function buttons(): HasMany
    {
        return $this->hasMany(AboutSectionButton::class)->orderBy('order_column');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AboutSectionItem::class)->orderBy('order_column');
    }

    public function activeItems(): HasMany
    {
        return $this->items()->where('is_active', true);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
        $this->addMediaCollection('background_image')->singleFile();
    }

    /**
     * Conversions run synchronously (nonQueued) since section images are
     * low-volume CMS uploads — waiting for a queue worker would make the
     * WebP variant unavailable immediately after upload for no real benefit.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->nonQueued();
    }
}
