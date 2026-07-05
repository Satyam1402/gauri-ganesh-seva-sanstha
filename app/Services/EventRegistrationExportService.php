<?php

namespace App\Services;

use App\Interfaces\EventRegistrationRepositoryInterface;
use App\Models\EventRegistration;
use App\Support\SimpleXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EventRegistrationExportService
{
    private const HEADINGS = [
        'Event',
        'Event Date',
        'Name',
        'Email',
        'Phone',
        'City',
        'Message',
        'Status',
        'Admin Notes',
        'Registered At',
    ];

    public function __construct(
        private EventRegistrationRepositoryInterface $registrations,
        private SimpleXlsxWriter $xlsxWriter,
    ) {}

    /**
     * Stream the filtered registrations as CSV. A UTF-8 BOM is emitted so
     * Excel renders Unicode names correctly.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(array $filters): StreamedResponse
    {
        $filename = 'event-registrations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADINGS);

            foreach ($this->registrations->exportCursor($filters) as $registration) {
                fputcsv($handle, $this->row($registration));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the filtered registrations as a real .xlsx workbook.
     *
     * @param  array<string, mixed>  $filters
     */
    public function downloadXlsx(array $filters): BinaryFileResponse
    {
        $filename = 'event-registrations-'.now()->format('Y-m-d-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'event-registrations-export');

        $rows = (function () use ($filters) {
            foreach ($this->registrations->exportCursor($filters) as $registration) {
                yield $this->row($registration);
            }
        })();

        $this->xlsxWriter->write($path, self::HEADINGS, $rows, 'Registrations');

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @return list<string|null>
     */
    private function row(EventRegistration $registration): array
    {
        return [
            $registration->event?->title,
            $registration->event?->start_date?->format('Y-m-d'),
            $registration->name,
            $registration->email,
            $registration->phone,
            $registration->city,
            $registration->message,
            $registration->status->label(),
            $registration->admin_notes,
            $registration->created_at->format('Y-m-d H:i'),
        ];
    }
}
