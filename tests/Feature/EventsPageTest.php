<?php

namespace Tests\Feature;

use App\Mail\EventRegistrationConfirmationMail;
use App\Mail\NewEventRegistrationNotificationMail;
use App\Models\Event;
use App\Models\EventCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class EventsPageTest extends TestCase
{
    use RefreshDatabase;

    private function category(): EventCategory
    {
        return EventCategory::create(['name' => 'Medical Camp', 'is_active' => true]);
    }

    private function event(array $overrides = []): Event
    {
        return Event::create(array_merge([
            'event_category_id' => $this->category()->id,
            'title' => 'Free Health Camp',
            'short_description' => 'Free checkups for everyone.',
            'full_description' => 'Full details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'status' => 'published',
        ], $overrides));
    }

    public function test_events_index_lists_upcoming_published_events(): void
    {
        $this->event(['title' => 'Upcoming Camp']);
        $this->event(['title' => 'Draft Camp', 'status' => 'draft']);

        $response = $this->get(route('events.index'));

        $response->assertOk();
        $response->assertSee('Upcoming Camp');
        $response->assertDontSee('Draft Camp');
    }

    public function test_past_events_tab_shows_only_past_events(): void
    {
        $this->event(['title' => 'Future Camp']);
        $this->event(['title' => 'Old Camp', 'start_date' => now()->subDays(30)->toDateString(), 'status' => 'completed']);

        $response = $this->get(route('events.index', ['when' => 'past']));

        $response->assertOk();
        $response->assertSee('Old Camp');
        $response->assertDontSee('Future Camp');
    }

    public function test_a_draft_event_detail_page_returns_404(): void
    {
        $event = $this->event(['status' => 'draft']);

        $this->get(route('events.show', $event))->assertNotFound();
    }

    public function test_a_published_event_detail_page_is_visible(): void
    {
        $event = $this->event(['venue' => 'Community Hall', 'city' => 'Pune']);

        $response = $this->get(route('events.show', $event));

        $response->assertOk();
        $response->assertSee('Free Health Camp');
        $response->assertSee('Community Hall');
        $response->assertSee('schema.org', false);
    }

    public function test_a_cancelled_event_shows_a_cancellation_notice(): void
    {
        $event = $this->event(['status' => 'cancelled']);

        $response = $this->get(route('events.show', $event));

        $response->assertOk();
        $response->assertSee('This event has been cancelled');
    }

    public function test_a_visitor_can_register_for_an_open_event_and_emails_are_queued(): void
    {
        Mail::fake();
        config(['events.admin_notification_email' => 'admin@example.com']);

        $event = $this->event(['requires_registration' => true, 'max_participants' => 50]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'city' => 'Pune',
            'message' => 'Looking forward to it!',
        ]);

        $response->assertRedirect(route('events.show', $event));
        $response->assertSessionHas('registration_status');

        $registration = $event->registrations()->firstOrFail();
        $this->assertSame('Asha Patil', $registration->name);
        $this->assertSame('pending', $registration->status->value);

        Mail::assertQueued(EventRegistrationConfirmationMail::class, fn ($mail) => $mail->hasTo('asha@example.com'));
        Mail::assertQueued(NewEventRegistrationNotificationMail::class, fn ($mail) => $mail->hasTo('admin@example.com'));
    }

    public function test_the_same_email_cannot_register_twice_for_one_event(): void
    {
        Mail::fake();

        $event = $this->event(['requires_registration' => true]);
        $event->registrations()->create([
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'status' => 'pending',
        ]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'Asha Again',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, $event->registrations()->count());
        Mail::assertNothingQueued();
    }

    public function test_registration_is_rejected_when_the_event_is_full(): void
    {
        Mail::fake();

        $event = $this->event(['requires_registration' => true, 'max_participants' => 1]);
        $event->registrations()->create([
            'name' => 'First Person',
            'email' => 'first@example.com',
            'phone' => '9876543210',
            'status' => 'confirmed',
        ]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'Second Person',
            'email' => 'second@example.com',
            'phone' => '9876543211',
        ]);

        $response->assertSessionHasErrors('registration');
        $this->assertSame(1, $event->registrations()->count());
        Mail::assertNothingQueued();
    }

    public function test_registration_is_rejected_when_the_event_does_not_require_it(): void
    {
        Mail::fake();

        $event = $this->event(['requires_registration' => false]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'Someone',
            'email' => 'someone@example.com',
            'phone' => '9876543210',
        ]);

        $response->assertSessionHasErrors('registration');
        $this->assertSame(0, $event->registrations()->count());
    }

    public function test_registration_is_rejected_for_a_past_event(): void
    {
        Mail::fake();

        $event = $this->event([
            'requires_registration' => true,
            'start_date' => now()->subDays(5)->toDateString(),
        ]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'Late Person',
            'email' => 'late@example.com',
            'phone' => '9876543210',
        ]);

        $response->assertSessionHasErrors('registration');
        $this->assertSame(0, $event->registrations()->count());
    }

    public function test_a_cancelled_registration_frees_its_seat(): void
    {
        Mail::fake();

        $event = $this->event(['requires_registration' => true, 'max_participants' => 1]);
        $event->registrations()->create([
            'name' => 'Dropped Out',
            'email' => 'dropped@example.com',
            'phone' => '9876543210',
            'status' => 'cancelled',
        ]);

        $response = $this->post(route('events.register', $event), [
            'name' => 'New Person',
            'email' => 'new@example.com',
            'phone' => '9876543211',
        ]);

        $response->assertRedirect(route('events.show', $event));
        $this->assertSame(2, $event->registrations()->count());
    }
}
