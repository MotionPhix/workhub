<?php

namespace App\Services\Report;

use Barryvdh\DomPDF\Facade\Pdf;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportExportService
{
    public function export(array $data, string $format): StreamedResponse
    {
        return match ($format) {
            'pdf' => $this->exportToPdf($data),
            'excel' => $this->exportToExcel($data),
            'csv' => $this->exportToCsv($data),
            default => throw new \InvalidArgumentException("Unsupported format: {$format}")
        };
    }

    protected function exportToPdf(array $data): StreamedResponse
    {
        $pdf = PDF::loadView('reports.export.pdf', compact('data'));

        return response()->stream(
            function () use ($pdf) {
                echo $pdf->output();
            },
            200,
            [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="report.pdf"',
            ]
        );
    }

    protected function exportToExcel(array $data): StreamedResponse
    {
        $spreadsheet = new Spreadsheet;
        $sheet = $spreadsheet->getActiveSheet();

        // Set headers
        $headers = ['Date', 'Title', 'Hours', 'Status', 'Department'];
        $sheet->fromArray($headers, null, 'A1');

        // Add data
        $rowIndex = 2;
        foreach ($data['details'] as $entry) {
            $sheet->fromArray([
                $entry->work_date,
                $entry->work_title,
                $entry->hours_worked,
                $entry->status,
                $entry->user->department,
            ], null, "A{$rowIndex}");
            $rowIndex++;
        }

        // Add summary sheet
        $summarySheet = $spreadsheet->createSheet();
        $summarySheet->setTitle('Summary');
        $summarySheet->fromArray([
            ['Total Entries', $data['summary']['total_entries']],
            ['Total Hours', $data['summary']['total_hours']],
            ['Average Hours', $data['summary']['average_hours']],
            ['Completion Rate', $data['summary']['completion_rate'].'%'],
        ]);

        $writer = new Xlsx($spreadsheet);

        return response()->stream(
            function () use ($writer) {
                $writer->save('php://output');
            },
            200,
            [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                'Content-Disposition' => 'attachment; filename="report.xlsx"',
            ]
        );
    }

    protected function exportToCsv(array $data): StreamedResponse
    {
        return response()->stream(
            function () use ($data) {
                $handle = fopen('php://output', 'w');

                // Headers
                fputcsv($handle, ['Date', 'Title', 'Hours', 'Status', 'Department']);

                // Data
                foreach ($data['details'] as $entry) {
                    fputcsv($handle, [
                        $entry->work_date,
                        $entry->work_title,
                        $entry->hours_worked,
                        $entry->status,
                        $entry->user->department,
                    ]);
                }

                fclose($handle);
            },
            200,
            [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="report.csv"',
            ]
        );
    }
}
