<?php

namespace App\Http\Controllers\Admin;

use App\Enums\VolunteerApplicationStatus;
use App\Enums\VolunteerAvailability;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteVolunteerApplicationsRequest;
use App\Http\Requests\Admin\BulkUpdateVolunteerApplicationStatusRequest;
use App\Http\Requests\Admin\UpdateVolunteerApplicationRequest;
use App\Interfaces\VolunteerApplicationRepositoryInterface;
use App\Models\VolunteerApplication;
use App\Services\VolunteerApplicationExportService;
use App\Services\VolunteerApplicationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class VolunteerApplicationController extends Controller
{
    private const FILTER_KEYS = ['q', 'status', 'availability', 'interest', 'from', 'to', 'sort', 'trashed'];

    public function __construct(
        private VolunteerApplicationRepositoryInterface $applications,
        private VolunteerApplicationService $applicationService,
        private VolunteerApplicationExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', VolunteerApplication::class);

        $filters = $request->only(self::FILTER_KEYS);

        return view('admin.volunteer-applications.index', [
            'applications' => $this->applications->adminSearch($filters, 20),
            'statuses' => VolunteerApplicationStatus::options(),
            'availabilities' => VolunteerAvailability::options(),
            'areasOfInterest' => config('volunteers.areas_of_interest', []),
            'statusCounts' => $this->applications->countsByStatus(),
            'filters' => $filters,
        ]);
    }

    public function show(VolunteerApplication $volunteerApplication): View
    {
        $this->authorize('view', $volunteerApplication);

        return view('admin.volunteer-applications.show', [
            'application' => $volunteerApplication->load(['media', 'reviewer:id,name']),
            'statuses' => VolunteerApplicationStatus::options(),
        ]);
    }

    public function update(UpdateVolunteerApplicationRequest $request, VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $this->authorize('update', $volunteerApplication);

        $this->applicationService->update($volunteerApplication, $request->validated());

        return back()->with('status', 'Application updated.');
    }

    public function approve(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        return $this->transition($volunteerApplication, VolunteerApplicationStatus::Approved, 'Application approved — the volunteer has been notified.');
    }

    public function reject(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        return $this->transition($volunteerApplication, VolunteerApplicationStatus::Rejected, 'Application rejected — the applicant has been notified.');
    }

    public function hold(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        return $this->transition($volunteerApplication, VolunteerApplicationStatus::OnHold, 'Application put on hold.');
    }

    public function archive(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        return $this->transition($volunteerApplication, VolunteerApplicationStatus::Archived, 'Application archived.');
    }

    public function restore(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $this->authorize('restore', $volunteerApplication);

        $this->applicationService->restore($volunteerApplication);

        return back()->with('status', 'Application restored.');
    }

    public function destroy(VolunteerApplication $volunteerApplication): RedirectResponse
    {
        $this->authorize('delete', $volunteerApplication);

        $this->applicationService->delete($volunteerApplication);

        return redirect()->route('admin.volunteer-applications.index')
            ->with('status', 'Application moved to trash.');
    }

    public function bulkUpdateStatus(BulkUpdateVolunteerApplicationStatusRequest $request): RedirectResponse
    {
        $this->authorize('update', VolunteerApplication::class);

        $count = $this->applicationService->bulkUpdateStatus(
            $request->validated('ids'),
            VolunteerApplicationStatus::from($request->validated('status')),
        );

        return back()->with('status', "{$count} application(s) updated.");
    }

    public function bulkDestroy(BulkDeleteVolunteerApplicationsRequest $request): RedirectResponse
    {
        $this->authorize('delete', VolunteerApplication::class);

        $count = $this->applicationService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} application(s) moved to trash.");
    }

    /**
     * Export the currently filtered applications as CSV or Excel (?format=xlsx).
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', VolunteerApplication::class);

        $filters = $request->only(self::FILTER_KEYS);

        return $request->query('format') === 'xlsx'
            ? $this->exportService->downloadXlsx($filters)
            : $this->exportService->streamCsv($filters);
    }

    /**
     * Stream a private applicant document (identity proof / resume) to an
     * authorized admin. These files live outside the public disk on purpose.
     */
    public function downloadDocument(VolunteerApplication $volunteerApplication, string $collection): Response
    {
        $this->authorize('view', $volunteerApplication);

        abort_unless(in_array($collection, VolunteerApplication::PRIVATE_DOCUMENT_COLLECTIONS, true), 404);

        $media = $volunteerApplication->getFirstMedia($collection);

        abort_if($media === null, 404);

        return response()->download($media->getPath(), $media->file_name);
    }

    private function transition(VolunteerApplication $application, VolunteerApplicationStatus $status, string $message): RedirectResponse
    {
        $this->authorize('update', $application);

        $this->applicationService->changeStatus($application, $status);

        return back()->with('status', $message);
    }
}
