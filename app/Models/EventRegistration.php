<?php

namespace App\Models;

use App\Enums\RegistrationStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Notifications\Notifiable;

class EventRegistration extends Model
{
    use Notifiable;

    protected $fillable = [
        'event_id',
        'name',
        'email',
        'phone',
        'city',
        'message',
        'status',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'status' => RegistrationStatus::class,
        ];
    }

    /**
     * @return array<string, string>
     */
    public function routeNotificationForMail(): array
    {
        return [$this->email => $this->name];
    }

    /**
     * Human-friendly registration number quoted in confirmations,
     * e.g. EVT-000042.
     */
    public function registrationNumber(): string
    {
        return sprintf('EVT-%06d', $this->id);
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function scopeCounted(Builder $query): Builder
    {
        return $query->whereIn('status', RegistrationStatus::countedValues());
    }
}
