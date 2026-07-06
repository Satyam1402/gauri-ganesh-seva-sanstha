<?php

namespace App\Services;

use App\Interfaces\VolunteerApplicationRepositoryInterface;
use App\Models\VolunteerApplication;
use App\Support\SimpleXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VolunteerApplicationExportService
{
    private const HEADINGS = [
        'Reference',
        'First Name',
        'Last Name',
        'Gender',
        'Date of Birth',
        'Email',
        'Phone',
        'Alternate Phone',
        'Address',
        'City',
        'State',
        'Country',
        'PIN Code',
        'Occupation',
        'Organization',
        'Skills',
        'Experience',
        'Areas of Interest',
        'Availability',
        'Emergency Contact',
        'Emergency Phone',
        'Medical Information',
        'Message',
        'Preferred Communication',
        'Status',
        'Admin Notes',
        'Reviewed By',
        'Reviewed At',
        'Applied At',
    ];

    public function __construct(
        private VolunteerApplicationRepositoryInterface $applications,
        private SimpleXlsxWriter $xlsxWriter,
    ) {}

    /**
     * Stream the filtered applications as CSV. A UTF-8 BOM is emitted so
     * Excel renders Unicode names correctly.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(array $filters): StreamedResponse
    {
        $filename = 'volunteer-applications-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADINGS);

            foreach ($this->applications->exportCursor($filters) as $application) {
                fputcsv($handle, $this->row($application));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the filtered applications as a real .xlsx workbook.
     *
     * @param  array<string, mixed>  $filters
     */
    public function downloadXlsx(array $filters): BinaryFileResponse
    {
        $filename = 'volunteer-applications-'.now()->format('Y-m-d-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'volunteer-applications-export');

        $rows = (function () use ($filters) {
            foreach ($this->applications->exportCursor($filters) as $application) {
                yield $this->row($application);
            }
        })();

        $this->xlsxWriter->write($path, self::HEADINGS, $rows, 'Applications');

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @return list<string|null>
     */
    private function row(VolunteerApplication $application): array
    {
        return [
            $application->reference,
            $application->first_name,
            $application->last_name,
            $application->gender->label(),
            $application->date_of_birth->format('Y-m-d'),
            $application->email,
            $application->phone,
            $application->alternate_phone,
            $application->address,
            $application->city,
            $application->state,
            $application->country,
            $application->pin_code,
            $application->occupation,
            $application->organization,
            $application->skills,
            $application->experience,
            implode(', ', $application->interestLabels()),
            $application->availability->label(),
            $application->emergency_contact_name,
            $application->emergency_contact_phone,
            $application->medical_information,
            $application->message,
            $application->preferred_communication_method->label(),
            $application->status->label(),
            $application->admin_notes,
            $application->reviewer?->name,
            $application->reviewed_at?->format('Y-m-d H:i'),
            $application->created_at->format('Y-m-d H:i'),
        ];
    }
}
