<?php

namespace Tests\Feature\Admin;

use App\Enums\Role as RoleEnum;
use App\Models\Faq;
use App\Models\FaqCategory;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FaqManagementTest extends TestCase
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

    private function category(array $overrides = []): FaqCategory
    {
        return FaqCategory::create(array_merge(['name' => 'Donations', 'is_active' => true], $overrides));
    }

    private function faq(array $overrides = []): Faq
    {
        return Faq::create(array_merge([
            'question' => 'Is my donation tax-deductible?',
            'answer' => 'Yes, under Section 80G of the Income Tax Act.',
            'status' => 'draft',
        ], $overrides));
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'question' => 'Can I volunteer without a long-term commitment?',
            'answer' => "Absolutely.\n\n- One-off drives\n- Regular roles",
            'status' => 'published',
            'is_featured' => 1,
            'display_order' => 3,
            'admin_notes' => 'Confirmed with the volunteer coordinator.',
        ], $overrides);
    }

    public function test_user_without_manage_faqs_permission_is_forbidden(): void
    {
        $viewer = User::factory()->create();
        $viewer->assignRole(RoleEnum::Viewer->value);

        $faq = $this->faq();

        $this->actingAs($viewer)->get(route('admin.faqs.index'))->assertForbidden();
        $this->actingAs($viewer)->post(route('admin.faqs.store'), $this->validPayload())->assertForbidden();
        $this->actingAs($viewer)->patch(route('admin.faqs.publish', $faq))->assertForbidden();
        $this->actingAs($viewer)->delete(route('admin.faqs.destroy', $faq))->assertForbidden();
        $this->actingAs($viewer)->get(route('admin.faq-categories.index'))->assertForbidden();

        $this->assertSame(1, Faq::count());
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('admin.faqs.index'))->assertRedirect(route('login'));
    }

    public function test_content_manager_can_create_a_faq_assigned_to_a_category(): void
    {
        $contentManager = User::factory()->create();
        $contentManager->assignRole(RoleEnum::ContentManager->value);
        $category = $this->category();

        $response = $this->actingAs($contentManager)->post(route('admin.faqs.store'), $this->validPayload([
            'faq_category_id' => $category->id,
        ]));

        $faq = Faq::firstOrFail();
        $response->assertRedirect(route('admin.faqs.edit', $faq));

        $this->assertTrue($faq->category->is($category));
        $this->assertSame('published', $faq->status->value);
        $this->assertNotNull($faq->published_at);
        $this->assertTrue($faq->is_featured);
        $this->assertSame(3, $faq->display_order);
        $this->assertSame($contentManager->id, $faq->created_by);
        $this->assertStringContainsString('<li>One-off drives</li>', $faq->answerHtml());
    }

    public function test_validation_rejects_missing_and_invalid_fields(): void
    {
        $response = $this->actingAs($this->admin())->post(route('admin.faqs.store'), [
            'question' => 'Hi?',
            'answer' => 'Short',
            'status' => 'live',
            'faq_category_id' => 999,
            'display_order' => -1,
        ]);

        $response->assertSessionHasErrors(['question', 'answer', 'status', 'faq_category_id', 'display_order']);
        $this->assertSame(0, Faq::count());
    }

    public function test_admin_can_edit_a_faq_and_change_its_category(): void
    {
        $first = $this->category(['name' => 'General']);
        $second = $this->category(['name' => 'Payment']);
        $faq = $this->faq(['faq_category_id' => $first->id]);

        $response = $this->actingAs($this->admin())->put(route('admin.faqs.update', $faq), $this->validPayload([
            'question' => 'Which payment methods do you accept?',
            'faq_category_id' => $second->id,
            'status' => 'unpublished',
            'is_featured' => 0,
        ]));

        $response->assertRedirect(route('admin.faqs.edit', $faq));

        $faq->refresh();
        $this->assertSame('Which payment methods do you accept?', $faq->question);
        $this->assertTrue($faq->category->is($second));
        $this->assertSame('unpublished', $faq->status->value);
        $this->assertFalse($faq->is_featured);
    }

    public function test_raw_html_and_unsafe_links_are_stripped_from_answers(): void
    {
        $this->actingAs($this->admin())->post(route('admin.faqs.store'), $this->validPayload([
            'answer' => 'Hello <script>alert(1)</script> [bad](javascript:alert(1)) [good](https://example.org) <img src=x onerror=alert(1)>',
        ]));

        $html = Faq::firstOrFail()->answerHtml();

        $this->assertStringNotContainsString('<script', $html);
        $this->assertStringNotContainsString('onerror', $html);
        $this->assertStringNotContainsString('javascript:', $html);
        $this->assertStringContainsString('<a href="https://example.org">good</a>', $html);
    }

    public function test_publish_sets_published_at_and_unpublish_reverts(): void
    {
        $faq = $this->faq();

        $this->actingAs($this->admin())->patch(route('admin.faqs.publish', $faq))
            ->assertSessionHas('status', 'FAQ published.');

        $faq->refresh();
        $this->assertSame('published', $faq->status->value);
        $this->assertNotNull($faq->published_at);

        $this->actingAs($this->admin())->patch(route('admin.faqs.unpublish', $faq))
            ->assertSessionHas('status', 'FAQ unpublished.');

        $this->assertSame('unpublished', $faq->refresh()->status->value);
    }

    public function test_admin_can_archive_feature_and_reorder(): void
    {
        $faq = $this->faq(['status' => 'published', 'published_at' => now()]);
        $admin = $this->admin();

        $this->actingAs($admin)->patch(route('admin.faqs.feature', $faq));
        $this->assertTrue($faq->refresh()->is_featured);

        $this->actingAs($admin)->patch(route('admin.faqs.order', $faq), ['display_order' => 9]);
        $this->assertSame(9, $faq->refresh()->display_order);

        $this->actingAs($admin)->patch(route('admin.faqs.archive', $faq));
        $this->assertSame('archived', $faq->refresh()->status->value);
    }

    public function test_admin_can_soft_delete_and_restore(): void
    {
        $faq = $this->faq();
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.faqs.destroy', $faq))
            ->assertRedirect(route('admin.faqs.index'));

        $this->assertSoftDeleted('faqs', ['id' => $faq->id]);

        $this->actingAs($admin)->get(route('admin.faqs.index', ['trashed' => 1]))
            ->assertOk()
            ->assertSee('Is my donation tax-deductible?');

        $this->actingAs($admin)->patch(route('admin.faqs.restore', $faq))
            ->assertRedirect(route('admin.faqs.index', ['trashed' => 1]));

        $this->assertNull($faq->fresh()->deleted_at);
    }

    public function test_bulk_actions_publish_move_and_delete(): void
    {
        $category = $this->category();
        $first = $this->faq(['question' => 'First question?']);
        $second = $this->faq(['question' => 'Second question?']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.faqs.bulk-update'), [
            'action' => 'publish',
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 FAQs published.');

        $this->assertSame('published', $first->refresh()->status->value);
        $this->assertNotNull($second->refresh()->published_at);

        $this->actingAs($admin)->post(route('admin.faqs.bulk-update'), [
            'action' => 'category',
            'faq_category_id' => $category->id,
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 FAQs moved.');

        $this->assertTrue($second->refresh()->category->is($category));

        $this->actingAs($admin)->post(route('admin.faqs.bulk-delete'), [
            'ids' => [$first->id, $second->id],
        ])->assertSessionHas('status', '2 FAQs moved to trash.');

        $this->assertSoftDeleted('faqs', ['id' => $first->id]);
    }

    public function test_index_search_and_filters_narrow_the_listing(): void
    {
        $donations = $this->category(['name' => 'Donations']);
        $this->faq(['question' => 'Is my donation tax-deductible?', 'faq_category_id' => $donations->id, 'status' => 'published', 'published_at' => now()]);
        $this->faq(['question' => 'Can I volunteer on weekends?', 'answer' => 'Yes, Sunday drives run weekly.', 'status' => 'draft']);

        $response = $this->actingAs($this->admin())->get(route('admin.faqs.index', ['category' => $donations->id, 'status' => 'published']));
        $response->assertOk();
        $this->assertSame(['Is my donation tax-deductible?'], $response->viewData('faqs')->pluck('question')->all());

        // Search matches the answer body, not only the question.
        $response = $this->actingAs($this->admin())->get(route('admin.faqs.index', ['q' => 'Sunday']));
        $this->assertSame(['Can I volunteer on weekends?'], $response->viewData('faqs')->pluck('question')->all());

        $response = $this->actingAs($this->admin())->get(route('admin.faqs.index', ['category' => 'none']));
        $this->assertSame(['Can I volunteer on weekends?'], $response->viewData('faqs')->pluck('question')->all());
    }

    public function test_show_page_displays_private_notes_to_admins(): void
    {
        $faq = $this->faq(['admin_notes' => 'Check with the accountant every April.']);

        $this->actingAs($this->admin())->get(route('admin.faqs.show', $faq))
            ->assertOk()
            ->assertSee('Check with the accountant every April.');
    }

    public function test_admin_can_manage_categories(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.faq-categories.store'), [
            'name' => 'Medical Assistance',
            'description' => 'Health camps and medical help.',
            'is_active' => 1,
        ])->assertRedirect(route('admin.faq-categories.index'));

        $category = FaqCategory::firstOrFail();
        $this->assertSame('medical-assistance', $category->slug);

        $this->actingAs($admin)->put(route('admin.faq-categories.update', $category), [
            'name' => 'Medical Help',
            'slug' => 'medical-help',
            'is_active' => 1,
        ])->assertRedirect(route('admin.faq-categories.index'));

        $this->assertSame('medical-help', $category->refresh()->slug);

        // Archive (toggle) then publish again.
        $this->actingAs($admin)->patch(route('admin.faq-categories.toggle', $category));
        $this->assertFalse($category->refresh()->is_active);
        $this->actingAs($admin)->patch(route('admin.faq-categories.toggle', $category));
        $this->assertTrue($category->refresh()->is_active);

        $second = $this->category(['name' => 'General']);
        $this->actingAs($admin)->postJson(route('admin.faq-categories.reorder'), ['order' => [$second->id, $category->id]])
            ->assertOk();
        $this->assertSame(0, $second->refresh()->order_column);
        $this->assertSame(1, $category->refresh()->order_column);
    }

    public function test_category_with_faqs_cannot_be_deleted_but_empty_one_can(): void
    {
        $used = $this->category(['name' => 'Used']);
        $empty = $this->category(['name' => 'Empty']);
        $this->faq(['faq_category_id' => $used->id]);
        $admin = $this->admin();

        $this->actingAs($admin)->delete(route('admin.faq-categories.destroy', $used))
            ->assertSessionHasErrors('category');
        $this->assertDatabaseHas('faq_categories', ['id' => $used->id]);

        $this->actingAs($admin)->delete(route('admin.faq-categories.destroy', $empty))
            ->assertRedirect(route('admin.faq-categories.index'));
        $this->assertDatabaseMissing('faq_categories', ['id' => $empty->id]);
    }
}
