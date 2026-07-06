<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Mail\EnquiryReplyMail;
use App\Models\ContactEnquiry;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ContactEnquiryManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    private function enquiry(array $overrides = []): ContactEnquiry
    {
        return ContactEnquiry::create(array_merge([
            'name' => 'Asha Patil',
            'email' => uniqid().'@example.com',
            'phone' => '9876543210',
            'subject' => 'Question about food drives',
            'category' => 'general',
            'message' => 'Which areas do your food drives cover?',
            'consented_at' => now(),
            'status' => 'new',
        ], $overrides));
    }

    public function test_user_without_manage_contact_messages_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.contact-enquiries.index'))->assertForbidden();
    }

    public function test_enquiries_are_listed_with_status_counts(): void
    {
        $this->enquiry(['name' => 'Prakash Jadhav']);

        $response = $this->actingAs($this->admin())->get(route('admin.contact-enquiries.index'));

        $response->assertOk();
        $response->assertSee('Prakash Jadhav');
    }

    public function test_the_detail_page_shows_the_full_enquiry(): void
    {
        $enquiry = $this->enquiry(['message' => 'A very specific question about Hadapsar.']);

        $response = $this->actingAs($this->admin())->get(route('admin.contact-enquiries.show', $enquiry));

        $response->assertOk();
        $response->assertSee('A very specific question about Hadapsar.');
        $response->assertSee($enquiry->reference);
    }

    public function test_status_notes_and_assignee_can_be_updated(): void
    {
        $enquiry = $this->enquiry();
        $admin = $this->admin();

        $this->actingAs($admin)->put(route('admin.contact-enquiries.update', $enquiry), [
            'status' => 'in_progress',
            'admin_notes' => 'Called the donor, awaiting receipt number.',
            'assigned_to' => $admin->id,
        ])->assertRedirect();

        $enquiry->refresh();
        $this->assertSame('in_progress', $enquiry->status->value);
        $this->assertSame('Called the donor, awaiting receipt number.', $enquiry->admin_notes);
        $this->assertSame($admin->id, $enquiry->assigned_to);
    }

    public function test_replying_stores_the_reply_and_queues_the_email(): void
    {
        Mail::fake();
        $enquiry = $this->enquiry();
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.contact-enquiries.reply', $enquiry), [
            'message' => 'Thank you for reaching out — our drives cover Hadapsar every Sunday.',
        ])->assertRedirect();

        $enquiry->refresh();
        $this->assertSame(1, $enquiry->replies()->count());
        $this->assertSame($admin->id, $enquiry->replies->first()->user_id);
        // A fresh enquiry moves to "In Progress" once replied to.
        $this->assertSame('in_progress', $enquiry->status->value);
        $this->assertNotNull($enquiry->replied_at);

        Mail::assertQueued(EnquiryReplyMail::class, fn ($mail) => $mail->hasTo($enquiry->email));
    }

    public function test_replying_does_not_downgrade_a_resolved_status(): void
    {
        Mail::fake();
        $enquiry = $this->enquiry(['status' => 'resolved']);

        $this->actingAs($this->admin())->post(route('admin.contact-enquiries.reply', $enquiry), [
            'message' => 'One more detail for you.',
        ])->assertRedirect();

        $this->assertSame('resolved', $enquiry->refresh()->status->value);
    }

    public function test_enquiries_can_be_bulk_status_updated(): void
    {
        $first = $this->enquiry();
        $second = $this->enquiry();

        $this->actingAs($this->admin())->post(route('admin.contact-enquiries.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'status' => 'spam',
        ])->assertRedirect();

        $this->assertSame('spam', $first->refresh()->status->value);
        $this->assertSame('spam', $second->refresh()->status->value);
    }

    public function test_enquiries_can_be_bulk_deleted(): void
    {
        $first = $this->enquiry();
        $second = $this->enquiry();

        $this->actingAs($this->admin())->post(route('admin.contact-enquiries.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_a_deleted_enquiry_can_be_restored(): void
    {
        $enquiry = $this->enquiry();
        $enquiry->delete();

        $this->actingAs($this->admin())
            ->patch(route('admin.contact-enquiries.restore', $enquiry))
            ->assertRedirect();

        $this->assertNull($enquiry->refresh()->deleted_at);
    }

    public function test_enquiries_can_be_exported_as_csv(): void
    {
        $this->enquiry(['name' => 'Prakash Jadhav', 'email' => 'prakash@example.com']);

        $response = $this->actingAs($this->admin())->get(route('admin.contact-enquiries.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Prakash Jadhav', $csv);
        $this->assertStringContainsString('prakash@example.com', $csv);
    }

    public function test_export_respects_the_category_filter(): void
    {
        $this->enquiry(['name' => 'GeneralPerson']);
        $this->enquiry(['name' => 'MediaPerson', 'category' => 'media']);

        $response = $this->actingAs($this->admin())->get(route('admin.contact-enquiries.export', ['category' => 'media']));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('MediaPerson', $csv);
        $this->assertStringNotContainsString('GeneralPerson', $csv);
    }

    public function test_a_missing_attachment_returns_404(): void
    {
        $enquiry = $this->enquiry();

        $this->actingAs($this->admin())
            ->get(route('admin.contact-enquiries.attachment', $enquiry))
            ->assertNotFound();
    }

    public function test_attachment_download_requires_permission(): void
    {
        $enquiry = $this->enquiry();
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)
            ->get(route('admin.contact-enquiries.attachment', $enquiry))
            ->assertForbidden();
    }
}
