<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class OrgProfile extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = [
        'legal_name',
        'short_name',
        'registration_no',
        'registration_date',
        'pan_no',
        'trust_deed_no',
        'section_80g_no',
        'section_12a_no',
        'established_year',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'established_year' => 'integer',
        ];
    }

    /**
     * Certificate scans (80G, 12A, Trust Deed, etc.) shown on the Trust
     * Certificates section — multiple files, no single-file limit.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('certificates');
        $this->addMediaCollection('legal_documents');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->nonQueued()
            ->performOnCollections('certificates');
    }
}
