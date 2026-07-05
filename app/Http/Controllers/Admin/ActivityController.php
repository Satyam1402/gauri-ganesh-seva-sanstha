<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ActivityStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteActivitiesRequest;
use App\Http\Requests\Admin\BulkPublishActivitiesRequest;
use App\Http\Requests\Admin\BulkUpdateActivityCategoryRequest;
use App\Http\Requests\Admin\StoreActivityRequest;
use App\Http\Requests\Admin\UpdateActivityRequest;
use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Interfaces\ActivityRepositoryInterface;
use App\Models\Activity;
use App\Services\ActivityService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepositoryInterface $activities,
        private ActivityCategoryRepositoryInterface $categories,
        private ActivityService $activityService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Activity::class);

        $filters = $request->only(['q', 'category', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.activities.index', [
            'activities' => $this->activities->adminSearch($filters, 15),
            'categories' => $this->categories->allOrdered(),
            'statuses' => ActivityStatus::options(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Activity::class);

        return view('admin.activities.create', [
            'categories' => $this->categories->allOrdered(),
            'statuses' => ActivityStatus::options(),
        ]);
    }

    public function store(StoreActivityRequest $request): RedirectResponse
    {
        $this->authorize('create', Activity::class);

        $activity = $this->activityService->createActivity($request->validated());

        return redirect()->route('admin.activities.edit', $activity)
            ->with('status', 'Activity created successfully.');
    }

    public function edit(Activity $activity): View
    {
        $this->authorize('update', $activity);

        return view('admin.activities.edit', [
            'activity' => $activity->load(['category', 'media', 'seo']),
            'categories' => $this->categories->allOrdered(),
            'statuses' => ActivityStatus::options(),
        ]);
    }

    public function update(UpdateActivityRequest $request, Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->activityService->updateActivity($activity, $request->validated());

        return redirect()->route('admin.activities.edit', $activity)
            ->with('status', 'Activity updated successfully.');
    }

    public function destroy(Activity $activity): RedirectResponse
    {
        $this->authorize('delete', $activity);

        $this->activityService->deleteActivity($activity);

        return redirect()->route('admin.activities.index')
            ->with('status', 'Activity moved to trash.');
    }

    public function restore(Activity $activity): RedirectResponse
    {
        $this->authorize('restore', $activity);

        $this->activityService->restoreActivity($activity);

        return redirect()->route('admin.activities.index', ['trashed' => 1])
            ->with('status', 'Activity restored successfully.');
    }

    public function toggleFeatured(Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->activityService->toggleFeatured($activity);

        return back()->with('status', $activity->is_featured ? 'Activity marked as featured.' : 'Activity removed from featured.');
    }

    public function publish(Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->activityService->publish($activity);

        return back()->with('status', 'Activity published.');
    }

    public function unpublish(Activity $activity): RedirectResponse
    {
        $this->authorize('update', $activity);

        $this->activityService->unpublish($activity);

        return back()->with('status', 'Activity unpublished.');
    }

    public function bulkDestroy(BulkDeleteActivitiesRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Activity::class);

        $count = $this->activityService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} activities moved to trash.");
    }

    public function bulkPublish(BulkPublishActivitiesRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Activity::class);

        $count = $this->activityService->bulkPublish($request->validated('ids'));

        return back()->with('status', "{$count} activities published.");
    }

    public function bulkUpdateCategory(BulkUpdateActivityCategoryRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Activity::class);

        $count = $this->activityService->bulkUpdateCategory(
            $request->validated('ids'),
            (int) $request->validated('activity_category_id'),
        );

        return back()->with('status', "{$count} activities moved to the selected category.");
    }
}
