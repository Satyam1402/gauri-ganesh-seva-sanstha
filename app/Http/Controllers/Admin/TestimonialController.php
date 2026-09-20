<?php

namespace App\Http\Controllers\Admin;

use App\Enums\TestimonialStatus;
use App\Enums\TestimonialType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteTestimonialsRequest;
use App\Http\Requests\Admin\BulkUpdateTestimonialStatusRequest;
use App\Http\Requests\Admin\StoreTestimonialRequest;
use App\Http\Requests\Admin\UpdateTestimonialOrderRequest;
use App\Http\Requests\Admin\UpdateTestimonialRequest;
use App\Interfaces\TestimonialRepositoryInterface;
use App\Models\Testimonial;
use App\Services\TestimonialService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestimonialController extends Controller
{
    public function __construct(
        private TestimonialRepositoryInterface $testimonials,
        private TestimonialService $testimonialService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Testimonial::class);

        $filters = $request->only(['q', 'type', 'status', 'featured', 'consent', 'sort', 'direction', 'trashed']);

        return view('admin.testimonials.index', [
            'testimonials' => $this->testimonials->adminSearch($filters, 15),
            'types' => TestimonialType::options(),
            'statuses' => TestimonialStatus::options(),
            'statistics' => $this->testimonials->statistics(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Testimonial::class);

        return view('admin.testimonials.create', $this->formData());
    }

    public function store(StoreTestimonialRequest $request): RedirectResponse
    {
        $this->authorize('create', Testimonial::class);

        $testimonial = $this->testimonialService->createTestimonial($request->validated(), $request->user()->id);

        return redirect()->route('admin.testimonials.edit', $testimonial)
            ->with('status', $this->savedMessage($testimonial, 'created'));
    }

    public function show(Testimonial $testimonial): View
    {
        $this->authorize('view', $testimonial);

        return view('admin.testimonials.show', [
            'testimonial' => $testimonial->load(['media', 'testimonialable', 'creator']),
        ]);
    }

    public function edit(Testimonial $testimonial): View
    {
        $this->authorize('update', $testimonial);

        return view('admin.testimonials.edit', [
            'testimonial' => $testimonial->load(['media', 'testimonialable']),
        ] + $this->formData());
    }

    public function update(UpdateTestimonialRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $this->testimonialService->updateTestimonial($testimonial, $request->validated());

        return redirect()->route('admin.testimonials.edit', $testimonial)
            ->with('status', $this->savedMessage($testimonial, 'updated'));
    }

    public function destroy(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('delete', $testimonial);

        $this->testimonialService->deleteTestimonial($testimonial);

        return redirect()->route('admin.testimonials.index')
            ->with('status', 'Testimonial moved to trash.');
    }

    public function restore(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('restore', $testimonial);

        $this->testimonialService->restoreTestimonial($testimonial);

        return redirect()->route('admin.testimonials.index', ['trashed' => 1])
            ->with('status', 'Testimonial restored successfully.');
    }

    public function toggleFeatured(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $this->testimonialService->toggleFeatured($testimonial);

        return back()->with('status', $testimonial->is_featured ? 'Testimonial marked as featured.' : 'Testimonial removed from featured.');
    }

    public function updateOrder(UpdateTestimonialOrderRequest $request, Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $this->testimonialService->updateDisplayOrder($testimonial, $request->validated('display_order'));

        return back()->with('status', 'Display order updated.');
    }

    public function publish(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        if (! $testimonial->canBePublished()) {
            return back()->with('error', 'Record the person\'s consent before publishing this testimonial.');
        }

        $this->testimonialService->publish($testimonial);

        return back()->with('status', 'Testimonial published.');
    }

    public function unpublish(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $this->testimonialService->unpublish($testimonial);

        return back()->with('status', 'Testimonial unpublished.');
    }

    public function archive(Testimonial $testimonial): RedirectResponse
    {
        $this->authorize('update', $testimonial);

        $this->testimonialService->archive($testimonial);

        return back()->with('status', 'Testimonial archived.');
    }

    public function bulkDestroy(BulkDeleteTestimonialsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Testimonial::class);

        $count = $this->testimonialService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} testimonials moved to trash.");
    }

    public function bulkUpdateStatus(BulkUpdateTestimonialStatusRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Testimonial::class);

        $ids = $request->validated('ids');

        return match ($request->validated('action')) {
            'publish' => $this->bulkPublishResponse($this->testimonialService->bulkPublish($ids)),
            'unpublish' => back()->with('status', $this->testimonialService->bulkUnpublish($ids).' testimonials unpublished.'),
            'archive' => back()->with('status', $this->testimonialService->bulkArchive($ids).' testimonials archived.'),
        };
    }

    /**
     * @param  array{published: int, skipped: int}  $result
     */
    private function bulkPublishResponse(array $result): RedirectResponse
    {
        $message = "{$result['published']} testimonials published.";

        if ($result['skipped'] > 0) {
            $message .= " {$result['skipped']} skipped — consent has not been recorded.";
        }

        return back()->with('status', $message);
    }

    /**
     * Explain when a save silently downgraded "published" to "pending
     * review" because consent was missing.
     */
    private function savedMessage(Testimonial $testimonial, string $verb): string
    {
        $message = "Testimonial {$verb} successfully.";

        if (request()->input('status') === TestimonialStatus::Published->value && ! $testimonial->isPublished()) {
            $message .= ' It was saved as Pending Review because consent has not been recorded.';
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'types' => TestimonialType::options(),
            'statuses' => TestimonialStatus::options(),
            'relatedOptions' => $this->testimonialService->relatedOptions(),
            'ratingOptions' => ['' => 'No rating'] + array_combine(
                range(Testimonial::MAX_RATING, Testimonial::MIN_RATING),
                array_map(fn (int $n) => "{$n} / ".Testimonial::MAX_RATING, range(Testimonial::MAX_RATING, Testimonial::MIN_RATING)),
            ),
        ];
    }
}
