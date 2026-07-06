<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Mail\VolunteerApplicationApprovedMail;
use App\Mail\VolunteerApplicationRejectedMail;
use App\Models\User;
use App\Models\VolunteerApplication;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class VolunteerApplicationManagementTest extends TestCase
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

    private function application(array $overrides = []): VolunteerApplication
    {
        return VolunteerApplication::create(array_merge([
            'first_name' => 'Asha',
            'last_name' => 'Patil',
            'gender' => 'female',
            'date_of_birth' => '1995-05-10',
            'email' => uniqid().'@example.com',
            'phone' => '9876543210',
            'address' => '12 Seva Marg',
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
            'consented_at' => now(),
            'status' => 'pending',
        ], $overrides));
    }

    public function test_user_without_manage_volunteers_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.volunteer-applications.index'))->assertForbidden();
    }

    public function test_applications_are_listed_with_status_counts(): void
    {
        $this->application(['first_name' => 'Sneha', 'last_name' => 'Kulkarni']);

        $response = $this->actingAs($this->admin())->get(route('admin.volunteer-applications.index'));

        $response->assertOk();
        $response->assertSee('Sneha Kulkarni');
    }

    public function test_the_detail_page_shows_the_full_application(): void
    {
        $application = $this->application(['medical_information' => 'Peanut allergy']);

        $response = $this->actingAs($this->admin())->get(route('admin.volunteer-applications.show', $application));

        $response->assertOk();
        $response->assertSee('Asha Patil');
        $response->assertSee('Peanut allergy');
        $response->assertSee($application->reference);
    }

    public function test_approving_stamps_the_reviewer_and_queues_the_approval_mail(): void
    {
        Mail::fake();
        $application = $this->application();
        $admin = $this->admin();

        $this->actingAs($admin)
            ->patch(route('admin.volunteer-applications.approve', $application))
            ->assertRedirect();

        $application->refresh();
        $this->assertSame('approved', $application->status->value);
        $this->assertSame($admin->id, $application->reviewed_by);
        $this->assertNotNull($application->reviewed_at);

        Mail::assertQueued(VolunteerApplicationApprovedMail::class, fn ($mail) => $mail->hasTo($application->email));
    }

    public function test_rejecting_queues_the_rejection_mail(): void
    {
        Mail::fake();
        $application = $this->application();

        $this->actingAs($this->admin())
            ->patch(route('admin.volunteer-applications.reject', $application))
            ->assertRedirect();

        $this->assertSame('rejected', $application->refresh()->status->value);
        Mail::assertQueued(VolunteerApplicationRejectedMail::class);
    }

    public function test_saving_the_same_status_does_not_requeue_a_status_mail(): void
    {
        Mail::fake();
        $application = $this->application(['status' => 'approved']);

        $this->actingAs($this->admin())->put(route('admin.volunteer-applications.update', $application), [
            'status' => 'approved',
            'admin_notes' => 'Orientation scheduled.',
        ])->assertRedirect();

        $this->assertSame('Orientation scheduled.', $application->refresh()->admin_notes);
        Mail::assertNotQueued(VolunteerApplicationApprovedMail::class);
    }

    public function test_an_application_can_be_archived(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin())
            ->patch(route('admin.volunteer-applications.archive', $application))
            ->assertRedirect();

        $this->assertSame('archived', $application->refresh()->status->value);
    }

    public function test_applications_can_be_bulk_status_updated(): void
    {
        Mail::fake();
        $first = $this->application();
        $second = $this->application();

        $this->actingAs($this->admin())->post(route('admin.volunteer-applications.bulk-status'), [
            'ids' => [$first->id, $second->id],
            'status' => 'under_review',
        ])->assertRedirect();

        $this->assertSame('under_review', $first->refresh()->status->value);
        $this->assertSame('under_review', $second->refresh()->status->value);
    }

    public function test_applications_can_be_bulk_deleted(): void
    {
        $first = $this->application();
        $second = $this->application();

        $this->actingAs($this->admin())->post(route('admin.volunteer-applications.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertRedirect();

        $this->assertSoftDeleted($first);
        $this->assertSoftDeleted($second);
    }

    public function test_a_deleted_application_can_be_restored(): void
    {
        $application = $this->application();
        $application->delete();

        $this->actingAs($this->admin())
            ->patch(route('admin.volunteer-applications.restore', $application))
            ->assertRedirect();

        $this->assertNull($application->refresh()->deleted_at);
    }

    public function test_applications_can_be_exported_as_csv(): void
    {
        $this->application(['first_name' => 'Sneha', 'last_name' => 'Kulkarni', 'email' => 'sneha@example.com']);

        $response = $this->actingAs($this->admin())->get(route('admin.volunteer-applications.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Sneha', $csv);
        $this->assertStringContainsString('sneha@example.com', $csv);
    }

    public function test_applications_can_be_exported_as_xlsx(): void
    {
        $this->application();

        $response = $this->actingAs($this->admin())->get(route('admin.volunteer-applications.export', ['format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_respects_the_status_filter(): void
    {
        $this->application(['first_name' => 'PendingPerson']);
        $this->application(['first_name' => 'ApprovedPerson', 'status' => 'approved']);

        $response = $this->actingAs($this->admin())->get(route('admin.volunteer-applications.export', ['status' => 'approved']));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('ApprovedPerson', $csv);
        $this->assertStringNotContainsString('PendingPerson', $csv);
    }

    public function test_a_missing_document_returns_404(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin())
            ->get(route('admin.volunteer-applications.document', [$application, 'resume']))
            ->assertNotFound();
    }

    public function test_an_unknown_document_collection_returns_404(): void
    {
        $application = $this->application();

        $this->actingAs($this->admin())
            ->get(route('admin.volunteer-applications.document', [$application, 'passwords']))
            ->assertNotFound();
    }

    public function test_document_download_requires_permission(): void
    {
        $application = $this->application();
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)
            ->get(route('admin.volunteer-applications.document', [$application, 'resume']))
            ->assertForbidden();
    }
}
