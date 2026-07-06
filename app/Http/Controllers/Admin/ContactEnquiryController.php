<?php

namespace App\Http\Controllers\Admin;

use App\Enums\EnquiryCategory;
use App\Enums\EnquiryStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\BulkDeleteContactEnquiriesRequest;
use App\Http\Requests\Admin\BulkUpdateContactEnquiryStatusRequest;
use App\Http\Requests\Admin\ReplyContactEnquiryRequest;
use App\Http\Requests\Admin\UpdateContactEnquiryRequest;
use App\Interfaces\ContactEnquiryRepositoryInterface;
use App\Models\ContactEnquiry;
use App\Models\User;
use App\Services\ContactEnquiryExportService;
use App\Services\ContactEnquiryService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class ContactEnquiryController extends Controller
{
    private const FILTER_KEYS = ['q', 'category', 'status', 'assigned', 'from', 'to', 'sort', 'trashed'];

    public function __construct(
        private ContactEnquiryRepositoryInterface $enquiries,
        private ContactEnquiryService $enquiryService,
        private ContactEnquiryExportService $exportService,
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', ContactEnquiry::class);

        $filters = $request->only(self::FILTER_KEYS);

        return view('admin.contact-enquiries.index', [
            'enquiries' => $this->enquiries->adminSearch($filters, 20),
            'statuses' => EnquiryStatus::options(),
            'categories' => EnquiryCategory::options(),
            'staff' => User::orderBy('name')->pluck('name', 'id'),
            'statusCounts' => $this->enquiries->countsByStatus(),
            'filters' => $filters,
        ]);
    }

    public function show(ContactEnquiry $contactEnquiry): View
    {
        $this->authorize('view', $contactEnquiry);

        return view('admin.contact-enquiries.show', [
            'enquiry' => $contactEnquiry->load(['media', 'assignee:id,name', 'replies.author:id,name']),
            'statuses' => EnquiryStatus::options(),
            'staff' => User::orderBy('name')->pluck('name', 'id'),
        ]);
    }

    public function update(UpdateContactEnquiryRequest $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('update', $contactEnquiry);

        $this->enquiryService->update($contactEnquiry, $request->validated());

        return back()->with('status', 'Enquiry updated.');
    }

    public function reply(ReplyContactEnquiryRequest $request, ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('reply', $contactEnquiry);

        $this->enquiryService->reply($contactEnquiry, $request->user(), $request->validated('message'));

        return back()->with('status', 'Reply sent to '.$contactEnquiry->email.'.');
    }

    public function restore(ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('restore', $contactEnquiry);

        $this->enquiryService->restore($contactEnquiry);

        return back()->with('status', 'Enquiry restored.');
    }

    public function destroy(ContactEnquiry $contactEnquiry): RedirectResponse
    {
        $this->authorize('delete', $contactEnquiry);

        $this->enquiryService->delete($contactEnquiry);

        return redirect()->route('admin.contact-enquiries.index')
            ->with('status', 'Enquiry moved to trash.');
    }

    public function bulkUpdateStatus(BulkUpdateContactEnquiryStatusRequest $request): RedirectResponse
    {
        $this->authorize('update', ContactEnquiry::class);

        $count = $this->enquiryService->bulkUpdateStatus(
            $request->validated('ids'),
            EnquiryStatus::from($request->validated('status')),
        );

        return back()->with('status', "{$count} enquiry(ies) updated.");
    }

    public function bulkDestroy(BulkDeleteContactEnquiriesRequest $request): RedirectResponse
    {
        $this->authorize('delete', ContactEnquiry::class);

        $count = $this->enquiryService->bulkDelete($request->validated('ids'));

        return back()->with('status', "{$count} enquiry(ies) moved to trash.");
    }

    /**
     * Export the currently filtered enquiries as CSV or Excel (?format=xlsx).
     */
    public function export(Request $request): Response
    {
        $this->authorize('viewAny', ContactEnquiry::class);

        $filters = $request->only(self::FILTER_KEYS);

        return $request->query('format') === 'xlsx'
            ? $this->exportService->downloadXlsx($filters)
            : $this->exportService->streamCsv($filters);
    }

    /**
     * Stream the visitor's attachment to an authorized admin. Attachments
     * live outside the public disk on purpose.
     */
    public function downloadAttachment(ContactEnquiry $contactEnquiry): Response
    {
        $this->authorize('view', $contactEnquiry);

        $media = $contactEnquiry->getFirstMedia('attachment');

        abort_if($media === null, 404);

        return response()->download($media->getPath(), $media->file_name);
    }
}
