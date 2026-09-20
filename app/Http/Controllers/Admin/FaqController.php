<?php

namespace App\Http\Controllers\Admin;

use App\Enums\FaqStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteFaqsRequest;
use App\Http\Requests\Admin\BulkUpdateFaqsRequest;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqOrderRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Interfaces\FaqCategoryRepositoryInterface;
use App\Interfaces\FaqRepositoryInterface;
use App\Models\Faq;
use App\Services\FaqService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FaqController extends Controller
{
    public function __construct(
        private FaqRepositoryInterface $faqs,
        private FaqCategoryRepositoryInterface $categories,
        private FaqService $faqService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Faq::class);

        $filters = $request->only(['q', 'category', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.faqs.index', [
            'faqs' => $this->faqs->adminSearch($filters, 15),
            'categories' => $this->categories->allOrdered(),
            'statuses' => FaqStatus::options(),
            'statistics' => $this->faqs->statistics(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Faq::class);

        return view('admin.faqs.create', $this->formData());
    }

    public function store(StoreFaqRequest $request): RedirectResponse
    {
        $this->authorize('create', Faq::class);

        $faq = $this->faqService->createFaq($request->validated(), $request->user()->id);

        return redirect()->route('admin.faqs.edit', $faq)
            ->with('status', 'FAQ created successfully.');
    }

    public function show(Faq $faq): View
    {
        $this->authorize('view', $faq);

        return view('admin.faqs.show', [
            'faq' => $faq->load(['category', 'creator']),
        ]);
    }

    public function edit(Faq $faq): View
    {
        $this->authorize('update', $faq);

        return view('admin.faqs.edit', ['faq' => $faq->load('category')] + $this->formData());
    }

    public function update(UpdateFaqRequest $request, Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->updateFaq($faq, $request->validated());

        return redirect()->route('admin.faqs.edit', $faq)
            ->with('status', 'FAQ updated successfully.');
    }

    public function destroy(Faq $faq): RedirectResponse
    {
        $this->authorize('delete', $faq);

        $this->faqService->deleteFaq($faq);

        return redirect()->route('admin.faqs.index')
            ->with('status', 'FAQ moved to trash.');
    }

    public function restore(Faq $faq): RedirectResponse
    {
        $this->authorize('restore', $faq);

        $this->faqService->restoreFaq($faq);

        return redirect()->route('admin.faqs.index', ['trashed' => 1])
            ->with('status', 'FAQ restored successfully.');
    }

    public function toggleFeatured(Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->toggleFeatured($faq);

        return back()->with('status', $faq->is_featured ? 'FAQ marked as featured.' : 'FAQ removed from featured.');
    }

    public function updateOrder(UpdateFaqOrderRequest $request, Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->updateDisplayOrder($faq, $request->validated('display_order'));

        return back()->with('status', 'Display order updated.');
    }

    public function publish(Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->publish($faq);

        return back()->with('status', 'FAQ published.');
    }

    public function unpublish(Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->unpublish($faq);

        return back()->with('status', 'FAQ unpublished.');
    }

    public function archive(Faq $faq): RedirectResponse
    {
        $this->authorize('update', $faq);

        $this->faqService->archive($faq);

        return back()->with('status', 'FAQ archived.');
    }

    public function bulkDestroy(BulkDeleteFaqsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Faq::class);

        $count = $this->faqService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} FAQs moved to trash.");
    }

    public function bulkUpdate(BulkUpdateFaqsRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Faq::class);

        $ids = $request->validated('ids');

        $message = match ($request->validated('action')) {
            'publish' => $this->faqService->bulkPublish($ids).' FAQs published.',
            'unpublish' => $this->faqService->bulkUnpublish($ids).' FAQs unpublished.',
            'archive' => $this->faqService->bulkArchive($ids).' FAQs archived.',
            'category' => $this->faqService->bulkAssignCategory($ids, $request->validated('faq_category_id') ?: null).' FAQs moved.',
        };

        return back()->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'categories' => $this->categories->allOrdered(),
            'statuses' => FaqStatus::options(),
        ];
    }
}
