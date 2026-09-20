<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReorderPartnerTypesRequest;
use App\Http\Requests\Admin\StorePartnerTypeRequest;
use App\Http\Requests\Admin\UpdatePartnerTypeRequest;
use App\Interfaces\PartnerTypeRepositoryInterface;
use App\Models\PartnerType;
use App\Services\PartnerTypeService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class PartnerTypeController extends Controller
{
    public function __construct(
        private PartnerTypeRepositoryInterface $types,
        private PartnerTypeService $typeService,
    ) {}

    public function index(): View
    {
        $this->authorize('viewAny', PartnerType::class);

        return view('admin.partner-types.index', [
            'types' => $this->types->allOrdered(),
        ]);
    }

    public function create(): View
    {
        $this->authorize('create', PartnerType::class);

        return view('admin.partner-types.create');
    }

    public function store(StorePartnerTypeRequest $request): RedirectResponse
    {
        $this->authorize('create', PartnerType::class);

        $this->typeService->createType($request->validated());

        return redirect()->route('admin.partner-types.index')
            ->with('status', 'Partnership type created successfully.');
    }

    public function edit(PartnerType $partnerType): View
    {
        $this->authorize('update', $partnerType);

        return view('admin.partner-types.edit', [
            'type' => $partnerType,
        ]);
    }

    public function update(UpdatePartnerTypeRequest $request, PartnerType $partnerType): RedirectResponse
    {
        $this->authorize('update', $partnerType);

        $this->typeService->updateType($partnerType, $request->validated());

        return redirect()->route('admin.partner-types.index')
            ->with('status', 'Partnership type updated successfully.');
    }

    public function toggle(PartnerType $partnerType): RedirectResponse
    {
        $this->authorize('update', $partnerType);

        $this->typeService->toggleActive($partnerType);

        return back()->with('status', $partnerType->is_active ? 'Type is now visible on the website.' : 'Type hidden — its partners are no longer shown on the website.');
    }

    public function destroy(PartnerType $partnerType): RedirectResponse
    {
        $this->authorize('delete', $partnerType);

        $this->typeService->deleteType($partnerType);

        return redirect()->route('admin.partner-types.index')
            ->with('status', 'Partnership type deleted successfully.');
    }

    public function reorder(ReorderPartnerTypesRequest $request): JsonResponse
    {
        $this->authorize('viewAny', PartnerType::class);

        $this->typeService->reorder($request->validated('order'));

        return response()->json(['status' => 'ok']);
    }
}
