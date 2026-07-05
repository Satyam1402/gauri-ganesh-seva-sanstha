<?php

namespace App\Models;

use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Donation extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'donation_campaign_id',
        'donor_name',
        'donor_email',
        'donor_phone',
        'donor_address',
        'pan_number',
        'amount',
        'currency',
        'payment_method',
        'transaction_id',
        'payment_status',
        'is_anonymous',
        'receipt_number',
        'donated_at',
        'remarks',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'donated_at' => 'datetime',
            'payment_method' => PaymentMethod::class,
            'payment_status' => PaymentStatus::class,
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (self $donation): void {
            if (empty($donation->reference)) {
                $donation->reference = (string) Str::uuid();
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(DonationCampaign::class, 'donation_campaign_id');
    }

    public function scopeCompleted(Builder $query): Builder
    {
        return $query->where('payment_status', PaymentStatus::Completed->value);
    }

    /**
     * Donor name as it may be shown publicly — anonymous donors stay hidden.
     */
    public function publicDonorName(): string
    {
        return $this->is_anonymous ? 'Anonymous Donor' : $this->donor_name;
    }

    public function isCompleted(): bool
    {
        return $this->payment_status === PaymentStatus::Completed;
    }

    /**
     * Merge new values into the gateway meta payload without losing old keys.
     *
     * @param  array<string, mixed>  $values
     */
    public function mergeMeta(array $values): void
    {
        $this->meta = array_merge($this->meta ?? [], $values);
    }
}
