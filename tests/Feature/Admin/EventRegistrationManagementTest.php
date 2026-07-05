<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Event;
use App\Models\EventCategory;
use App\Models\EventRegistration;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EventRegistrationManagementTest extends TestCase
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

    private function registration(array $overrides = []): EventRegistration
    {
        $event = Event::create([
            'event_category_id' => EventCategory::create(['name' => 'Medical Camp', 'is_active' => true])->id,
            'title' => 'Health Camp '.uniqid(),
            'short_description' => 'Camp.',
            'full_description' => 'Details.',
            'start_date' => now()->addDays(10)->toDateString(),
            'requires_registration' => true,
            'status' => 'published',
        ]);

        return $event->registrations()->create(array_merge([
            'name' => 'Asha Patil',
            'email' => 'asha@example.com',
            'phone' => '9876543210',
            'city' => 'Pune',
            'status' => 'pending',
        ], $overrides));
    }

    public function test_user_without_manage_events_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $this->actingAs($viewer)->get(route('admin.event-registrations.index'))->assertForbidden();
    }

    public function test_registrations_are_listed_with_status_counts(): void
    {
        $registration = $this->registration();

        $response = $this->actingAs($this->admin())->get(route('admin.event-registrations.index'));

        $response->assertOk();
        $response->assertSee('Asha Patil');
        $response->assertSee($registration->event->title);
    }

    public function test_a_registration_status_and_notes_can_be_updated(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->admin())->put(route('admin.event-registrations.update', $registration), [
            'status' => 'confirmed',
            'admin_notes' => 'Called and confirmed attendance.',
        ])->assertRedirect();

        $registration->refresh();
        $this->assertSame('confirmed', $registration->status->value);
        $this->assertSame('Called and confirmed attendance.', $registration->admin_notes);
    }

    public function test_a_registration_can_be_deleted(): void
    {
        $registration = $this->registration();

        $this->actingAs($this->admin())
            ->delete(route('admin.event-registrations.destroy', $registration))
            ->assertRedirect();

        $this->assertNull(EventRegistration::find($registration->id));
    }

    public function test_registrations_can_be_exported_as_csv(): void
    {
        $this->registration();

        $response = $this->actingAs($this->admin())->get(route('admin.event-registrations.export'));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Asha Patil', $csv);
        $this->assertStringContainsString('asha@example.com', $csv);
    }

    public function test_registrations_can_be_exported_as_xlsx(): void
    {
        $this->registration();

        $response = $this->actingAs($this->admin())->get(route('admin.event-registrations.export', ['format' => 'xlsx']));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    public function test_export_respects_the_status_filter(): void
    {
        $this->registration();
        $this->registration(['email' => 'confirmed@example.com', 'name' => 'Confirmed Person', 'status' => 'confirmed']);

        $response = $this->actingAs($this->admin())->get(route('admin.event-registrations.export', ['status' => 'confirmed']));

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Confirmed Person', $csv);
        $this->assertStringNotContainsString('Asha Patil', $csv);
    }
}
