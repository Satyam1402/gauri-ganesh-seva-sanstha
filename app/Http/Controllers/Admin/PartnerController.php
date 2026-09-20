<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PartnerStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeletePartnersRequest;
use App\Http\Requests\Admin\BulkUpdatePartnersRequest;
use App\Http\Requests\Admin\StorePartnerRequest;
use App\Http\Requests\Admin\UpdatePartnerOrderRequest;
use App\Http\Requests\Admin\UpdatePartnerRequest;
use App\Interfaces\PartnerRepositoryInterface;
use App\Interfaces\PartnerTypeRepositoryInterface;
use App\Models\Partner;
use App\Services\PartnerService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PartnerController extends Controller
{
    public function __construct(
        private PartnerRepositoryInterface $partners,
        private PartnerTypeRepositoryInterface $types,
        private PartnerService $partnerService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Partner::class);

        $filters = $request->only(['q', 'type', 'status', 'featured', 'sort', 'direction', 'trashed']);

        return view('admin.partners.index', [
            'partners' => $this->partners->adminSearch($filters, 15),
            'types' => $this->types->allOrdered(),
            'statuses' => PartnerStatus::options(),
            'statistics' => $this->partners->statistics(),
            'filters' => $filters,
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', Partner::class);

        return view('admin.partners.create', $this->formData());
    }

    public function store(StorePartnerRequest $request): RedirectResponse
    {
        $this->authorize('create', Partner::class);

        $partner = $this->partnerService->createPartner($request->validated(), $request->user()->id);

        return redirect()->route('admin.partners.edit', $partner)
            ->with('status', 'Partner created successfully.');
    }

    public function show(Partner $partner): View
    {
        $this->authorize('view', $partner);

        return view('admin.partners.show', [
            'partner' => $partner->load(['type', 'media', 'creator']),
        ]);
    }

    public function edit(Partner $partner): View
    {
        $this->authorize('update', $partner);

        return view('admin.partners.edit', ['partner' => $partner->load(['type', 'media'])] + $this->formData());
    }

    public function update(UpdatePartnerRequest $request, Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->updatePartner($partner, $request->validated());

        return redirect()->route('admin.partners.edit', $partner)
            ->with('status', 'Partner updated successfully.');
    }

    public function destroy(Partner $partner): RedirectResponse
    {
        $this->authorize('delete', $partner);

        $this->partnerService->deletePartner($partner);

        return redirect()->route('admin.partners.index')
            ->with('status', 'Partner moved to trash.');
    }

    public function restore(Partner $partner): RedirectResponse
    {
        $this->authorize('restore', $partner);

        $this->partnerService->restorePartner($partner);

        return redirect()->route('admin.partners.index', ['trashed' => 1])
            ->with('status', 'Partner restored successfully.');
    }

    public function toggleFeatured(Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->toggleFeatured($partner);

        return back()->with('status', $partner->is_featured ? 'Partner marked as featured.' : 'Partner removed from featured.');
    }

    public function updateOrder(UpdatePartnerOrderRequest $request, Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->updateDisplayOrder($partner, $request->validated('display_order'));

        return back()->with('status', 'Display order updated.');
    }

    public function activate(Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->activate($partner);

        return back()->with('status', 'Partner activated — now visible on the website.');
    }

    public function deactivate(Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->deactivate($partner);

        return back()->with('status', 'Partner deactivated.');
    }

    public function archive(Partner $partner): RedirectResponse
    {
        $this->authorize('update', $partner);

        $this->partnerService->archive($partner);

        return back()->with('status', 'Partner archived.');
    }

    public function bulkDestroy(BulkDeletePartnersRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Partner::class);

        $count = $this->partnerService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} partners moved to trash.");
    }

    public function bulkUpdate(BulkUpdatePartnersRequest $request): RedirectResponse
    {
        $this->authorize('viewAny', Partner::class);

        $ids = $request->validated('ids');

        $message = match ($request->validated('action')) {
            'activate' => $this->partnerService->bulkSetStatus($ids, PartnerStatus::Active).' partners activated.',
            'deactivate' => $this->partnerService->bulkSetStatus($ids, PartnerStatus::Inactive).' partners deactivated.',
            'archive' => $this->partnerService->bulkSetStatus($ids, PartnerStatus::Archived).' partners archived.',
        };

        return back()->with('status', $message);
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'types' => $this->types->allOrdered(),
            'statuses' => PartnerStatus::options(),
        ];
    }
}
