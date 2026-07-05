<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\ActivityCategory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ActivitiesPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_activities_index_only_lists_published_activities(): void
    {
        $category = ActivityCategory::create(['name' => 'Medical Camps', 'is_active' => true]);

        Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Published Camp',
            'short_description' => 'Visible on the public site.',
            'full_description' => 'Details.',
            'activity_date' => now(),
            'status' => 'published',
        ]);

        Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Draft Camp',
            'short_description' => 'Should stay hidden.',
            'full_description' => 'Details.',
            'activity_date' => now(),
            'status' => 'draft',
        ]);

        $response = $this->get(route('activities.index'));

        $response->assertOk();
        $response->assertSee('Published Camp');
        $response->assertDontSee('Draft Camp');
    }

    public function test_a_draft_activity_detail_page_returns_404(): void
    {
        $category = ActivityCategory::create(['name' => 'Medical Camps', 'is_active' => true]);

        $activity = Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Draft Camp',
            'short_description' => 'Should stay hidden.',
            'full_description' => 'Details.',
            'activity_date' => now(),
            'status' => 'draft',
        ]);

        $this->get(route('activities.show', $activity))->assertNotFound();
    }

    public function test_a_published_activity_detail_page_is_visible(): void
    {
        $category = ActivityCategory::create(['name' => 'Medical Camps', 'is_active' => true]);

        $activity = Activity::create([
            'activity_category_id' => $category->id,
            'title' => 'Published Camp',
            'short_description' => 'Visible on the public site.',
            'full_description' => 'Full detailed description.',
            'activity_date' => now(),
            'status' => 'published',
        ]);

        $response = $this->get(route('activities.show', $activity));

        $response->assertOk();
        $response->assertSee('Published Camp');
    }
}
