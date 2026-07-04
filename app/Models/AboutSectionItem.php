<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class AboutSectionItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'about_section_id',
        'title',
        'subtitle',
        'description',
        'icon',
        'link_url',
        'is_active',
        'order_column',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function aboutSection(): BelongsTo
    {
        return $this->belongsTo(AboutSection::class);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('image')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->nonQueued();
    }
}
