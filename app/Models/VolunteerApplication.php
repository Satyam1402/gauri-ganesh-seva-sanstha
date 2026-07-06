<?php

namespace App\Models;

use App\Enums\CommunicationMethod;
use App\Enums\Gender;
use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class VolunteerApplication extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    /**
     * Media collections holding sensitive applicant documents. They live on
     * the private "local" disk and are only served through the authorized
     * admin download route — never via a public URL.
     */
    public const PRIVATE_DOCUMENT_COLLECTIONS = ['identity_proof', 'resume'];

    protected $fillable = [
        'first_name',
        'last_name',
        'gender',
        'date_of_birth',
        'email',
        'phone',
        'alternate_phone',
        'address',
        'city',
        'state',
        'country',
        'pin_code',
        'occupation',
        'organization',
        'skills',
        'experience',
        'areas_of_interest',
        'availability',
        'emergency_contact_name',
        'emergency_contact_phone',
        'medical_information',
        'message',
        'preferred_communication_method',
        'consented_at',
        'status',
        'admin_notes',
        'reviewed_by',
        'reviewed_at',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'areas_of_interest' => 'array',
            'consented_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'gender' => Gender::class,
            'availability' => VolunteerAvailability::class,
            'preferred_communication_method' => CommunicationMethod::class,
            'status' => VolunteerApplicationStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $application): void {
            $application->reference ??= (string) Str::uuid();
        });
    }

    /**
     * Admin who last changed the application status.
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function scopeStatus(Builder $query, VolunteerApplicationStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    public function fullName(): string
    {
        return trim($this->first_name.' '.$this->last_name);
    }

    public function age(): ?int
    {
        return $this->date_of_birth?->age;
    }

    /**
     * Human-readable labels for the stored area-of-interest keys. Unknown
     * keys (areas removed from config later) fall back to a title-cased key
     * so historical applications keep rendering.
     *
     * @return list<string>
     */
    public function interestLabels(): array
    {
        $areas = config('volunteers.areas_of_interest', []);

        return array_map(
            fn (string $key) => $areas[$key] ?? Str::title(str_replace('_', ' ', $key)),
            $this->areas_of_interest ?? [],
        );
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('profile_photo')->singleFile();

        foreach (self::PRIVATE_DOCUMENT_COLLECTIONS as $collection) {
            $this->addMediaCollection($collection)->singleFile()->useDisk('local');
        }
    }

    /**
     * Conversions only apply to the profile photo — identity proofs and
     * resumes may be PDFs and are stored untouched on the private disk.
     */
    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('webp')
            ->format('webp')
            ->quality(82)
            ->performOnCollections('profile_photo')
            ->nonQueued();

        $this->addMediaConversion('thumb')
            ->format('webp')
            ->quality(80)
            ->width(320)
            ->performOnCollections('profile_photo')
            ->nonQueued();
    }
}
