<?php

namespace App\Services;

use App\Interfaces\ContactEnquiryRepositoryInterface;
use App\Models\ContactEnquiry;
use App\Support\SimpleXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ContactEnquiryExportService
{
    private const HEADINGS = [
        'Reference',
        'Name',
        'Email',
        'Phone',
        'Subject',
        'Category',
        'Message',
        'Status',
        'Assigned To',
        'Admin Notes',
        'Last Replied At',
        'Received At',
    ];

    public function __construct(
        private ContactEnquiryRepositoryInterface $enquiries,
        private SimpleXlsxWriter $xlsxWriter,
    ) {}

    /**
     * Stream the filtered enquiries as CSV. A UTF-8 BOM is emitted so Excel
     * renders Unicode names correctly.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(array $filters): StreamedResponse
    {
        $filename = 'contact-enquiries-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADINGS);

            foreach ($this->enquiries->exportCursor($filters) as $enquiry) {
                fputcsv($handle, $this->row($enquiry));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the filtered enquiries as a real .xlsx workbook.
     *
     * @param  array<string, mixed>  $filters
     */
    public function downloadXlsx(array $filters): BinaryFileResponse
    {
        $filename = 'contact-enquiries-'.now()->format('Y-m-d-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'contact-enquiries-export');

        $rows = (function () use ($filters) {
            foreach ($this->enquiries->exportCursor($filters) as $enquiry) {
                yield $this->row($enquiry);
            }
        })();

        $this->xlsxWriter->write($path, self::HEADINGS, $rows, 'Enquiries');

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @return list<string|null>
     */
    private function row(ContactEnquiry $enquiry): array
    {
        return [
            $enquiry->reference,
            $enquiry->name,
            $enquiry->email,
            $enquiry->phone,
            $enquiry->subject,
            $enquiry->category->label(),
            $enquiry->message,
            $enquiry->status->label(),
            $enquiry->assignee?->name,
            $enquiry->admin_notes,
            $enquiry->replied_at?->format('Y-m-d H:i'),
            $enquiry->created_at->format('Y-m-d H:i'),
        ];
    }
}
