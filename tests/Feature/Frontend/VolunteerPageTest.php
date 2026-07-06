<?php

namespace Tests\Feature\Frontend;

use App\Mail\NewVolunteerApplicationNotificationMail;
use App\Mail\VolunteerApplicationReceivedMail;
use App\Models\VolunteerApplication;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class VolunteerPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Asha',
            'last_name' => 'Patil',
            'gender' => 'female',
            'date_of_birth' => '1995-05-10',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'address' => '12 Seva Marg, Shivaji Nagar',
            'city' => 'Pune',
            'state' => 'Maharashtra',
            'country' => 'India',
            'pin_code' => '411001',
            'occupation' => 'Teacher',
            'skills' => 'Teaching, first aid',
            'areas_of_interest' => ['education_teaching'],
            'availability' => 'weekends',
            'emergency_contact_name' => 'Ravi Patil',
            'emergency_contact_phone' => '9123456780',
            'preferred_communication_method' => 'email',
            'consent' => '1',
        ], $overrides);
    }

    public function test_the_volunteer_page_renders(): void
    {
        $response = $this->get(route('volunteer.create'));

        $response->assertOk();
        $response->assertSee('Become a Volunteer');
        $response->assertSee('Areas of Interest');
        $response->assertSee('application/ld+json', escape: false);
    }

    public function test_a_valid_application_is_stored_and_emails_are_queued(): void
    {
        Mail::fake();

        $response = $this->post(route('volunteer.store'), $this->validPayload());

        $response->assertRedirect(route('volunteer.thank-you'));

        $application = VolunteerApplication::query()->where('email', 'asha@example.com')->first();
        $this->assertNotNull($application);
        $this->assertSame('pending', $application->status->value);
        $this->assertSame(['education_teaching'], $application->areas_of_interest);
        $this->assertNotNull($application->reference);
        $this->assertNotNull($application->consented_at);

        Mail::assertQueued(VolunteerApplicationReceivedMail::class, fn ($mail) => $mail->hasTo('asha@example.com'));
        Mail::assertQueued(NewVolunteerApplicationNotificationMail::class);
    }

    public function test_the_thank_you_page_requires_a_fresh_submission(): void
    {
        $this->get(route('volunteer.thank-you'))
            ->assertRedirect(route('volunteer.create'));
    }

    public function test_consent_is_required(): void
    {
        $response = $this->post(route('volunteer.store'), $this->validPayload(['consent' => null]));

        $response->assertSessionHasErrors('consent');
        $this->assertSame(0, VolunteerApplication::count());
    }

    public function test_applicants_must_be_at_least_sixteen(): void
    {
        $response = $this->post(route('volunteer.store'), $this->validPayload([
            'date_of_birth' => now()->subYears(15)->toDateString(),
        ]));

        $response->assertSessionHasErrors('date_of_birth');
    }

    public function test_an_unknown_area_of_interest_is_rejected(): void
    {
        $response = $this->post(route('volunteer.store'), $this->validPayload([
            'areas_of_interest' => ['space_travel'],
        ]));

        $response->assertSessionHasErrors('areas_of_interest.0');
    }

    public function test_a_duplicate_open_application_is_rejected(): void
    {
        Mail::fake();

        $this->post(route('volunteer.store'), $this->validPayload());

        $response = $this->post(route('volunteer.store'), $this->validPayload([
            'phone' => '9000000000',
        ]));

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, VolunteerApplication::count());
    }

    public function test_a_rejected_applicant_can_apply_again(): void
    {
        Mail::fake();

        $this->post(route('volunteer.store'), $this->validPayload());
        VolunteerApplication::query()->update(['status' => 'rejected']);

        $response = $this->post(route('volunteer.store'), $this->validPayload());

        $response->assertRedirect(route('volunteer.thank-you'));
        $this->assertSame(2, VolunteerApplication::count());
    }

    public function test_uploads_are_attached_to_their_media_collections(): void
    {
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');

        $response = $this->post(route('volunteer.store'), $this->validPayload([
            'profile_photo' => UploadedFile::fake()->image('me.jpg', 300, 300),
            'resume' => UploadedFile::fake()->create('resume.pdf', 100, 'application/pdf'),
        ]));

        $response->assertRedirect(route('volunteer.thank-you'));

        $application = VolunteerApplication::query()->firstOrFail();
        $this->assertNotNull($application->getFirstMedia('profile_photo'));
        $this->assertNotNull($application->getFirstMedia('resume'));
        // Sensitive documents must land on the private disk.
        $this->assertSame('local', $application->getFirstMedia('resume')->disk);
    }

    public function test_a_resume_with_a_disallowed_extension_is_rejected(): void
    {
        $response = $this->post(route('volunteer.store'), $this->validPayload([
            'resume' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ]));

        $response->assertSessionHasErrors('resume');
    }
}
