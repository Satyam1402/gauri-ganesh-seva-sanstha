<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Partner;
use App\Models\PartnerType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class PartnerManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('public');
    }

    private function admin(): User
    {
        $admin = User::factory()->create();
        $admin->assignRole(RoleEnum::Admin->value);

        return $admin;
    }

    private function type(array $overrides = []): PartnerType
    {
        return PartnerType::create(array_merge(['name' => 'Sponsor', 'is_active' => true], $overrides));
    }

    private function partner(array $overrides = []): Partner
    {
        return Partner::create(array_merge([
            'name' => 'Sunrise CSR Trust',
            'short_description' => 'Funds our annual school-kit drive.',
            'status' => 'draft',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'name' => 'ABC Foundation',
            'short_description' => 'Employee volunteering partner.',
            'full_description' => "Matched giving since 2021.\n\n- Volunteering days\n- Winter drive sponsor",
            'website_url' => 'https://abc-foundation.example.org/about',
            'email' => 'csr@abc-foundation.example.org',
            'phone' => '+91 98765 43210',
            'city' => 'Mumbai',
            'state' => 'Maharashtra',
            'country' => 'India',
            'started_on' => '2021-01-15',
            'logo_alt' => 'ABC Foundation wordmark',
            'status' => 'active',
            'is_featured' => 1,
            'display_order' => 2,
            'admin_notes' => 'MoU ref 2021/07.',
        ], $overrides);
    }

    public function test_user_without_manage_partners_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $partner = $this->partner();

        $this->actingAs($viewer)->get(route('admin.partners.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.partners.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($viewer)->patch(route('admin.partners.activate', $partner))->assertForbidden();
        $this->actingAs($viewer)->delete(route('admin.partners.destroy', $partner))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.partner-types.index'))->assertForbidden();

        $this->assertSame(1, Partner::count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.partners.index'))->assertRedirect(route('login'));
    }

    public function test_content_manager_can_create_a_partner_with_a_logo(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);
        $type = $this->type();

        $response = $this->actingAs($contentManager)->post(route('admin.partners.store'), $this->validPayload([
            'partner_type_id' => $type->id,
            'logo' => UploadedFile::fake()->image('abc.png', 600, 200),
            'cover_image' => UploadedFile::fake()->image('cover.jpg', 1200, 400),
        ]));

        $partner = Partner::firstOrFail();
        $response->assertRedirect(route('admin.partners.edit', $partner));

        $this->assertSame('abc-foundation', $partner->slug);
        $this->assertTrue($partner->type->is($type));
        $this->assertSame('active', $partner->status->value);
        $this->assertTrue($partner->is_featured);
        $this->assertSame(2, $partner->display_order);
        $this->assertSame('ABC Foundation wordmark', $partner->logoAlt());
        $this->assertSame('abc-foundation.example.org', $partner->websiteHost());
        $this->assertSame($contentManager->id, $partner->created_by);
        $this->assertNotNull($partner->getFirstMedia('logo'));
        $this->assertNotNull($partner->getFirstMedia('cover_image'));
    }

    public function test_validation_rejects_missing_and_invalid_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.partners.store'), [
            'name' => '',
            'status' => 'live',
            'partner_type_id' => 999,
            'website_url' => 'javascript:alert(1)',
            'email' => 'not-an-email',
            'phone' => 'call me maybe',
            'started_on' => '2022-01-01',
            'ended_on' => '2021-01-01',
            'logo' => UploadedFile::fake()->create('logo.svg', 10, 'image/svg+xml'),
        ]);

        $response->assertSessionHasErrors(['name', 'status', 'partner_type_id', 'website_url', 'email', 'phone', 'ended_on', 'logo']);
        $this->assertSame(0, Partner::count());
    }

    public function test_website_url_must_be_http_or_https(): void
    {
        foreach (['ftp://files.example.org', 'data:text/html,hi', 'example.org'] as $bad) {
            $this->actingAs($this->admin())->post(route('admin.partners.store'), $this->validPayload(['website_url' => $bad]))
                ->assertSessionHasErrors('website_url');
        }

        $this->actingAs($this->admin())->post(route('admin.partners.store'), $this->validPayload(['website_url' => 'http://example.org']))
            ->assertSessionHasNoErrors();

        $this->assertSame(1, Partner::count());
    }

    public function test_admin_can_edit_a_partner_and_replace_or_remove_its_logo(): void
    {
        $partner = $this->partner();
        $partner->addMedia(UploadedFile::fake()->image('old.png', 300, 100))->toMediaCollection('logo');

        $response = $this->actingAs($this->admin())->put(route('admin.partners.update', $partner), $this->validPayload([
            'name' => 'Sunrise CSR Trust (Pune)',
            'status' => 'inactive',
            'is_featured' => 0,
            'logo' => UploadedFile::fake()->image('new.png', 300, 100),
        ]));

        $response->assertRedirect(route('admin.partners.edit', $partner));

        $partner->refresh();
        $this->assertSame('Sunrise CSR Trust (Pune)', $partner->name);
        $this->assertSame('sunrise-csr-trust', $partner->slug, 'slug is kept on rename unless explicitly changed');
        $this->assertSame('inactive', $partner->status->value);
        $this->assertFalse($partner->is_featured);
        $this->assertCount(1, $partner->getMedia('logo'));
        $this->assertSame('new.png', $partner->getFirstMedia('logo')->file_name);

        $this->actingAs($this->admin())->put(route('admin.partners.update', $partner), $this->validPayload(['remove_logo' => 1]));

        $this->assertNull($partner->refresh()->getFirstMedia('logo'));
        $this->assertSame('ABC Foundation wordmark', $partner->logoAlt());

        // Blank alt text falls back to "{name} logo" so a logo never ships without an accessible name.
        $this->actingAs($this->admin())->put(route('admin.partners.update', $partner), $this->validPayload(['name' => 'Sunrise CSR Trust', 'logo_alt' => '']));
        $this->assertSame('Sunrise CSR Trust logo', $partner->refresh()->logoAlt());
    }

    public function test_activate_and_deactivate_toggle_public_visibility(): void
    {
        $partner = $this->partner();

        $this->actingAs($this->admin())->patch(route('admin.partners.activate', $partner))
            ->assertSessionHas('status');
        $this->assertSame('active', $partner->refresh()->status->value);
        $this->get(route('partners.show', $partner))->assertOk();

        $this->actingAs($this->admin())->patch(route('admin.partners.deactivate', $partner))
            ->assertSessionHas('status', 'Partner deactivated.');
        $this->assertSame('inactive', $partner->refresh()->status->value);
        $this->get(route('partners.show', $partner))->assertNotFound();
    }

    public function test_admin_can_archive_feature_and_reorder(): void
    {
        $partner = $this->partner(['status' => 'active']);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.partners.feature', $partner));
        $this->assertTrue($partner->refresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.partners.order', $partner), ['display_order' => 9]);
        $this->assertSame(9, $partner->refresh()->display_order);

        $this->actingAs($admin)->patch(route('admin.partners.order', $partner), ['display_order' => -1])
            ->assertSessionHasErrors('display_order');

        $this->actingAs($admin)->patch(route('admin.partners.archive', $partner));
        $this->assertSame('archived', $partner->refresh()->status->value);
    }

    public function test_admin_can_soft_delete_and_restore(): void
    {
        $partner = $this->partner();
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.partners.destroy', $partner))
            ->assertRedirect(route('admin.partners.index'));

        $this->assertSoftDeleted('partners', ['id' => $partner->id]);

        $this->actingAs($admin)->get(route('admin.partners.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Sunrise CSR Trust');

        $this->actingAs($admin)->patch(route('admin.partners.restore', $partner))
            ->assertRedirect(route('admin.partners.index', ['trashed' => 1]));

        $this->assertNull($partner->fresh()->deleted_at);
    }

    public function test_bulk_activate_deactivate_and_delete(): void
    {
        $first = $this->partner(['name' => 'First Org']);
        $second = $this->partner(['name' => 'Second Org']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.partners.bulk-update'), [
            'action' => 'activate',
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 partners activated.');

        $this->assertSame('active', $first->refresh()->status->value);

        $this->actingAs($admin)->post(route('admin.partners.bulk-update'), [
            'action' => 'deactivate',
            'ids' => [$second->id],
        ])->assertSessionHas('status', '1 partners deactivated.');

        $this->assertSame('inactive', $second->refresh()->status->value);

        $this->actingAs($admin)->post(route('admin.partners.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 partners moved to trash.');

        $this->assertSoftDeleted('partners', ['id' => $first->id]);
        $this->assertSoftDeleted('partners', ['id' => $second->id]);
    }

    public function test_index_search_filters_and_sorting(): void
    {
        $sponsor = $this->type(['name' => 'Sponsor']);
        $this->partner(['name' => 'Zeta Sponsor', 'partner_type_id' => $sponsor->id, 'status' => 'active', 'city' => 'Pune']);
        $this->partner(['name' => 'Alpha NGO', 'status' => 'draft', 'city' => 'Mumbai']);

        $response = $this->actingAs($this->admin())->get(route('admin.partners.index', ['type' => $sponsor->id, 'status' => 'active']));
        $response->assertOk();
        $this->assertSame(['Zeta Sponsor'], $response->viewData('partners')->pluck('name')->all());

        $response = $this->actingAs($this->admin())->get(route('admin.partners.index', ['q' => 'Mumbai']));
        $this->assertSame(['Alpha NGO'], $response->viewData('partners')->pluck('name')->all());

        $response = $this->actingAs($this->admin())->get(route('admin.partners.index', ['type' => 'none']));
        $this->assertSame(['Alpha NGO'], $response->viewData('partners')->pluck('name')->all());

        $response = $this->actingAs($this->admin())->get(route('admin.partners.index', ['sort' => 'name', 'direction' => 'asc']));
        $this->assertSame(['Alpha NGO', 'Zeta Sponsor'], $response->viewData('partners')->pluck('name')->all());
    }

    public function test_show_page_displays_private_contact_and_notes_to_admins(): void
    {
        $partner = $this->partner(['email' => 'csr@example.org', 'admin_notes' => 'Renewal due March.']);

        $this->actingAs($this->admin())->get(route('admin.partners.show', $partner))
            ->assertOk()
            ->assertSee('csr@example.org')
            ->assertSee('Renewal due March.');
    }

    public function test_admin_can_manage_partner_types(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.partner-types.store'), [
            'name' => 'Media Partner',
            'description' => 'Outlets that amplify our work.',
            'is_active' => 1,
        ])->assertRedirect(route('admin.partner-types.index'));

        $type = PartnerType::firstOrFail();
        $this->assertSame('media-partner', $type->slug);

        $this->actingAs($admin)->put(route('admin.partner-types.update', $type), [
            'name' => 'Media Partners',
            'slug' => 'media',
            'is_active' => 1,
        ])->assertRedirect(route('admin.partner-types.index'));
        $this->assertSame('media', $type->refresh()->slug);

        $this->actingAs($admin)->patch(route('admin.partner-types.toggle', $type));
        $this->assertFalse($type->refresh()->is_active);

        $second = $this->type(['name' => 'Sponsor']);
        $this->actingAs($admin)->postJson(route('admin.partner-types.reorder'), ['order' => [$second->id, $type->id]])->assertOk();
        $this->assertSame(0, $second->refresh()->order_column);
        $this->assertSame(1, $type->refresh()->order_column);
    }

    public function test_type_with_partners_cannot_be_deleted_but_empty_one_can(): void
    {
        $used = $this->type(['name' => 'Used']);
        $empty = $this->type(['name' => 'Empty']);
        $this->partner(['partner_type_id' => $used->id]);
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.partner-types.destroy', $used))->assertSessionHasErrors('type');
        $this->assertDatabaseHas('partner_types', ['id' => $used->id]);

        $this->actingAs($admin)->delete(route('admin.partner-types.destroy', $empty))->assertRedirect(route('admin.partner-types.index'));
        $this->assertDatabaseMissing('partner_types', ['id' => $empty->id]);
    }
}
