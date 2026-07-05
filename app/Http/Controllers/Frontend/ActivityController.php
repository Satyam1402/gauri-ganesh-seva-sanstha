<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Interfaces\ActivityCategoryRepositoryInterface;
use App\Interfaces\ActivityRepositoryInterface;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ActivityController extends Controller
{
    public function __construct(
        private ActivityRepositoryInterface $activities,
        private ActivityCategoryRepositoryInterface $categories,
    ) {}

    public function index(Request $request): View
    {
        $filters = $request->only(['q', 'category', 'sort']);

        return view('frontend.activities.index', [
            'activities' => $this->activities->publishedPaginated($filters, 12),
            'categories' => $this->categories->activeOrdered(),
            'filters' => $filters,
            'latest' => $this->activities->latest(3),
        ]);
    }

    public function show(Activity $activity): View
    {
        abort_unless($activity->status->value === 'published', 404);

        return view('frontend.activities.show', [
            'activity' => $activity->load(['category', 'media', 'seo.ogImage']),
            'related' => $this->activities->related($activity, 3),
        ]);
    }
}
