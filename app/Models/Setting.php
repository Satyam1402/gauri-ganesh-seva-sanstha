<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

/**
 * One row per scalar site setting. Rows are never read directly by the
 * application — go through App\Services\SettingsService / setting().
 */
class Setting extends Model implements HasMedia
{
    use InteractsWithMedia;

    public const TYPE_STRING = 'string';

    public const TYPE_TEXT = 'text';

    public const TYPE_BOOLEAN = 'boolean';

    public const TYPE_INTEGER = 'integer';

    public const TYPE_DECIMAL = 'decimal';

    public const TYPE_JSON = 'json';

    public const TYPE_MEDIA = 'media';

    protected $fillable = [
        'group',
        'key',
        'value',
        'type',
    ];

    /**
     * Cast the stored string to the declared type.
     */
    public function castValue(): mixed
    {
        return match ($this->type) {
            self::TYPE_BOOLEAN => filter_var($this->value, FILTER_VALIDATE_BOOLEAN),
            self::TYPE_INTEGER => $this->value === null || $this->value === '' ? null : (int) $this->value,
            self::TYPE_DECIMAL => $this->value === null || $this->value === '' ? null : (float) $this->value,
            self::TYPE_JSON => $this->value === null ? null : json_decode($this->value, true),
            default => $this->value,
        };
    }

    /**
     * Media settings use two collections: "image" (converted to WebP) for
     * logos/OG images/QR codes, and "raw" for files that must not be
     * transcoded (the .ico favicon).
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png', 'image/webp']);

        $this->addMediaCollection('raw')
            ->singleFile()
            ->acceptsMimeTypes(['image/png', 'image/x-icon', 'image/vnd.microsoft.icon']);
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(85)
            ->width(1200)
            ->nonQueued()
            ->performOnCollections('image');
    }
}
