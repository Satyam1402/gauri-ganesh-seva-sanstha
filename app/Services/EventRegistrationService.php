<?php

namespace App\Services;

use App\Enums\RegistrationStatus;
use App\Interfaces\EventRegistrationRepositoryInterface;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Notifications\Events\EventRegistrationConfirmation;
use App\Notifications\Events\NewEventRegistrationAlert;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class EventRegistrationService
{
    public function __construct(
        private EventRegistrationRepositoryInterface $registrations,
        private EventService $eventService,
        private NotificationService $notifier,
    ) {}

    /**
     * Register a visitor for an event. The capacity check runs inside a
     * transaction with the insert so two simultaneous submissions cannot
     * both claim the last seat.
     *
     * @param  array<string, mixed>  $data
     */
    public function register(Event $event, array $data): EventRegistration
    {
        if (! $event->requires_registration || $event->status->value !== 'published') {
            throw ValidationException::withMessages([
                'registration' => 'Registration is not open for this event.',
            ]);
        }

        if ($event->isPast()) {
            throw ValidationException::withMessages([
                'registration' => 'This event has already taken place.',
            ]);
        }

        $registration = DB::transaction(function () use ($event, $data) {
            if ($event->max_participants !== null) {
                $taken = $event->activeRegistrations()->lockForUpdate()->count();

                if ($taken >= $event->max_participants) {
                    throw ValidationException::withMessages([
                        'registration' => 'This event is fully booked.',
                    ]);
                }
            }

            return $event->registrations()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'],
                'city' => $data['city'] ?? null,
                'message' => $data['message'] ?? null,
                'status' => RegistrationStatus::Pending->value,
            ]);
        });

        $registration->setRelation('event', $event);

        $this->notifier->send($registration, new EventRegistrationConfirmation($registration));
        $this->notifyAdmin($registration);

        $this->eventService->forgetCache();

        return $registration;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(EventRegistration $registration, array $data): EventRegistration
    {
        $this->registrations->update($registration, [
            'status' => $data['status'],
            'admin_notes' => $data['admin_notes'] ?? null,
        ]);

        $this->eventService->forgetCache();

        return $registration->refresh();
    }

    public function delete(EventRegistration $registration): bool
    {
        $deleted = $this->registrations->delete($registration);
        $this->eventService->forgetCache();

        return $deleted;
    }

    private function notifyAdmin(EventRegistration $registration): void
    {
        $this->notifier->notifyAdmins(
            new NewEventRegistrationAlert($registration),
            config('events.admin_notification_email'),
        );
    }
}
