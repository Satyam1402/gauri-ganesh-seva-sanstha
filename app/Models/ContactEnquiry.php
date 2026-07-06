<?php

namespace App\Models;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class ContactEnquiry extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'subject',
        'category',
        'message',
        'consented_at',
        'ip_address',
        'status',
        'admin_notes',
        'assigned_to',
        'replied_at',
    ];

    protected function casts(): array
    {
        return [
            'consented_at' => 'datetime',
            'replied_at' => 'datetime',
            'category' => EnquiryCategory::class,
            'status' => EnquiryStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $enquiry): void {
            $enquiry->reference ??= (string) Str::uuid();
        });
    }

    /**
     * Staff member the enquiry is assigned to (future-ready workflow field).
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    public function replies(): HasMany
    {
        return $this->hasMany(ContactEnquiryReply::class)->orderBy('created_at');
    }

    public function scopeStatus(Builder $query, EnquiryStatus $status): Builder
    {
        return $query->where('status', $status->value);
    }

    /**
     * Visitor attachments may be sensitive (complaints, documents) — they
     * live on the private "local" disk and are only served through the
     * authorized admin download route.
     */
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('attachment')->singleFile()->useDisk('local');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // No conversions: attachments are stored untouched (may be PDFs/DOCs).
    }
}
