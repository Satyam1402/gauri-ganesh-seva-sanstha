<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class HomeSectionItem extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'home_section_id',
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

    public function homeSection(): BelongsTo
    {
        return $this->belongsTo(HomeSection::class);
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
