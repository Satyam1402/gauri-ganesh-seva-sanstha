<?php

namespace Tests\Feature\Frontend;

use App\Mail\EnquiryAcknowledgementMail;
use App\Mail\NewEnquiryNotificationMail;
use App\Models\ContactEnquiry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ContactPageTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'subject' => 'Question about weekend food drives',
            'category' => 'general',
            'message' => 'I would like to know which areas your weekend food drives currently cover.',
            'consent' => '1',
        ], $overrides);
    }

    public function test_the_contact_page_renders(): void
    {
        $response = $this->get(route('contact'));

        $response->assertOk();
        $response->assertSee('Contact Us');
        $response->assertSee('Send Us a Message');
        $response->assertSee('ContactPage', escape: false);
    }

    public function test_a_valid_enquiry_is_stored_and_emails_are_queued(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertRedirect(route('contact').'#contact-form');
        $response->assertSessionHas('enquiry_status');

        $enquiry = ContactEnquiry::query()->where('email', 'asha@example.com')->first();
        $this->assertNotNull($enquiry);
        $this->assertSame('new', $enquiry->status->value);
        $this->assertSame('general', $enquiry->category->value);
        $this->assertNotNull($enquiry->reference);
        $this->assertNotNull($enquiry->consented_at);
        $this->assertNotNull($enquiry->ip_address);

        Mail::assertQueued(EnquiryAcknowledgementMail::class, fn ($mail) => $mail->hasTo('asha@example.com'));
        Mail::assertQueued(NewEnquiryNotificationMail::class);
    }

    public function test_the_honeypot_silently_discards_bot_submissions(): void
    {
        Mail::fake();

        $response = $this->post(route('contact.store'), $this->validPayload([
            'website' => 'https://spam.example.com',
        ]));

        // Bots see a normal success redirect, but nothing is stored or sent.
        $response->assertRedirect(route('contact').'#contact-form');
        $this->assertSame(0, ContactEnquiry::count());
        Mail::assertNothingQueued();
    }

    public function test_consent_is_required(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['consent' => null]));

        $response->assertSessionHasErrors('consent');
        $this->assertSame(0, ContactEnquiry::count());
    }

    public function test_an_unknown_category_is_rejected(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload(['category' => 'hacking']));

        $response->assertSessionHasErrors('category');
    }

    public function test_an_attachment_is_stored_on_the_private_disk(): void
    {
        Mail::fake();
        Storage::fake('public');
        Storage::fake('local');

        $this->post(route('contact.store'), $this->validPayload([
            'attachment' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
        ]))->assertRedirect(route('contact').'#contact-form');

        $enquiry = ContactEnquiry::query()->firstOrFail();
        $media = $enquiry->getFirstMedia('attachment');
        $this->assertNotNull($media);
        $this->assertSame('local', $media->disk);
    }

    public function test_an_attachment_with_a_disallowed_extension_is_rejected(): void
    {
        $response = $this->post(route('contact.store'), $this->validPayload([
            'attachment' => UploadedFile::fake()->create('malware.exe', 100, 'application/octet-stream'),
        ]));

        $response->assertSessionHasErrors('attachment');
    }

    public function test_recaptcha_is_skipped_when_not_configured(): void
    {
        Mail::fake();
        config(['services.recaptcha.secret_key' => null]);

        $this->post(route('contact.store'), $this->validPayload())
            ->assertSessionHasNoErrors();
    }

    public function test_recaptcha_is_required_when_configured(): void
    {
        config(['services.recaptcha.secret_key' => 'test-secret']);

        $response = $this->post(route('contact.store'), $this->validPayload());

        $response->assertSessionHasErrors('g-recaptcha-response');
        $this->assertSame(0, ContactEnquiry::count());
    }
}
