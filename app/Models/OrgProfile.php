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
        'address_line',
        'city',
        'state',
        'pin_code',
        'phone_primary',
        'phone_secondary',
        'email_primary',
        'email_secondary',
        'office_hours',
        'whatsapp_number',
        'emergency_phone',
        'map_embed_url',
        'facebook_url',
        'instagram_url',
        'twitter_url',
        'youtube_url',
        'linkedin_url',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'established_year' => 'integer',
        ];
    }

    /**
     * Full address as a single display line, or null when unset.
     */
    public function addressLine(): ?string
    {
        $parts = array_filter([$this->address_line, $this->city, $this->state, $this->pin_code]);

        return $parts === [] ? null : implode(', ', $parts);
    }

    /**
     * Configured social profiles as [platform => url].
     *
     * @return array<string, string>
     */
    public function socialLinks(): array
    {
        return array_filter([
            'facebook' => $this->facebook_url,
            'instagram' => $this->instagram_url,
            'twitter' => $this->twitter_url,
            'youtube' => $this->youtube_url,
            'linkedin' => $this->linkedin_url,
        ]);
    }

    /**
     * wa.me link for the WhatsApp button, or null when no number is set.
     */
    public function whatsappLink(): ?string
    {
        $digits = preg_replace('/\D/', '', (string) $this->whatsapp_number);

        return $digits ? 'https://wa.me/'.$digits : null;
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
