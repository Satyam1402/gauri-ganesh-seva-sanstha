<?php

namespace App\Http\Controllers\Admin;

use App\Enums\RegistrationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateEventRegistrationRequest;
use App\Interfaces\EventRegistrationRepositoryInterface;
use App\Models\Event;
use App\Models\EventRegistration;
use App\Services\EventRegistrationExportService;
use App\Services\EventRegistrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class EventRegistrationController extends Controller
{
    public function __construct(
        private EventRegistrationRepositoryInterface $registrations,
        private EventRegistrationService $registrationService,
        private EventRegistrationExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', EventRegistration::class);

        $filters = $request->only(['q', 'event', 'status', 'from', 'to']);

        return view('admin.event-registrations.index', [
            'registrations' => $this->registrations->adminSearch($filters, 20),
            'events' => Event::orderBy('start_date', 'desc')->pluck('title', 'id'),
            'statuses' => RegistrationStatus::options(),
            'statusCounts' => $this->registrations->countsByStatus(),
            'filters' => $filters,
        ]);
    }

    public function update(UpdateEventRegistrationRequest $request, EventRegistration $eventRegistration): RedirectResponse
    {
        $this->authorize('update', $eventRegistration);

        $this->registrationService->update($eventRegistration, $request->validated());

        return back()->with('status', 'Registration updated.');
    }

    public function destroy(EventRegistration $eventRegistration): RedirectResponse
    {
        $this->authorize('delete', $eventRegistration);

        $this->registrationService->delete($eventRegistration);

        return back()->with('status', 'Registration deleted.');
    }

    /**
     * Export the currently filtered registrations as CSV or Excel (?format=xlsx).
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', EventRegistration::class);

        $filters = $request->only(['q', 'event', 'status', 'from', 'to']);

        return $request->query('format') === 'xlsx'
            ? $this->exportService->downloadXlsx($filters)
            : $this->exportService->streamCsv($filters);
    }
}
