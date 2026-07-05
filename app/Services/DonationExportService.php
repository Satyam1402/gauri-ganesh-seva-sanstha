<?php

namespace App\Services;

use App\Interfaces\DonationRepositoryInterface;
use App\Models\Donation;
use App\Support\SimpleXlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonationExportService
{
    private const HEADINGS = [
        'Receipt No.',
        'Donor Name',
        'Email',
        'Phone',
        'Address',
        'PAN',
        'Campaign',
        'Amount',
        'Currency',
        'Payment Method',
        'Transaction ID',
        'Payment Status',
        'Anonymous',
        'Donated At',
        'Remarks',
    ];

    public function __construct(
        private DonationRepositoryInterface $donations,
        private SimpleXlsxWriter $xlsxWriter,
    ) {}

    /**
     * Stream the filtered donations as CSV. A UTF-8 BOM is emitted so Excel
     * renders ₹/Unicode donor names correctly.
     *
     * @param  array<string, mixed>  $filters
     */
    public function streamCsv(array $filters): StreamedResponse
    {
        $filename = 'donations-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($filters): void {
            $handle = fopen('php://output', 'w');
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, self::HEADINGS);

            foreach ($this->donations->exportCursor($filters) as $donation) {
                fputcsv($handle, $this->row($donation));
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Build the filtered donations as a real .xlsx workbook.
     *
     * @param  array<string, mixed>  $filters
     */
    public function downloadXlsx(array $filters): BinaryFileResponse
    {
        $filename = 'donations-'.now()->format('Y-m-d-His').'.xlsx';
        $path = tempnam(sys_get_temp_dir(), 'donations-export');

        $rows = (function () use ($filters) {
            foreach ($this->donations->exportCursor($filters) as $donation) {
                yield $this->row($donation, numericAmount: true);
            }
        })();

        $this->xlsxWriter->write($path, self::HEADINGS, $rows, 'Donations');

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend();
    }

    /**
     * @return list<string|float|null>
     */
    private function row(Donation $donation, bool $numericAmount = false): array
    {
        return [
            $donation->receipt_number,
            $donation->donor_name,
            $donation->donor_email,
            $donation->donor_phone,
            $donation->donor_address,
            $donation->pan_number,
            $donation->campaign?->name ?? 'General Donation',
            $numericAmount ? (float) $donation->amount : number_format((float) $donation->amount, 2, '.', ''),
            $donation->currency,
            $donation->payment_method->label(),
            $donation->transaction_id,
            $donation->payment_status->label(),
            $donation->is_anonymous ? 'Yes' : 'No',
            $donation->donated_at->format('Y-m-d H:i'),
            $donation->remarks,
        ];
    }
}
