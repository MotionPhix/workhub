<?php

namespace App\Services;

use App\Exports\GenericDataExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportService
{
    /**
     * Advanced export method with extensive customization
     *
     * @param  array  $data  Data to export
     * @param  array  $options  Export customization options
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     *
     * @throws \Exception
     */
    public function advancedExport(array $data, array $options = [])
    {
        // Validate input
        $this->validateExportRequest($data, $options);

        // Default options
        $defaultOptions = [
            'filename' => 'export_'.now()->format('YmdHis'),
            'format' => 'xlsx',
            'columns' => null, // Specific columns to export
            'filters' => [], // Additional data filtering
            'styling' => [
                'header_color' => '#4a4a4a',
                'alternate_row_color' => '#f4f4f4',
            ],
            'metadata' => [
                'title' => 'Export',
                'subject' => 'Data Export',
                'creator' => auth()->user()->name ?? 'System',
            ],
            'password' => null, // Optional password protection
        ];

        // Merge default options with provided options
        $exportOptions = array_merge($defaultOptions, $options);

        // Apply filters if specified
        $filteredData = $this->applyDataFilters($data, $exportOptions['filters']);

        // Select specific columns if specified
        $processedData = $this->selectColumns($filteredData, $exportOptions['columns']);

        try {
            // Log export attempt
            $this->logExportAttempt($exportOptions, count($processedData));

            // Export based on format
            return $this->performExport($processedData, $exportOptions);
        } catch (\Exception $e) {
            // Log detailed error
            Log::error('Export failed', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'export_options' => $exportOptions,
            ]);

            // Throw a more user-friendly exception
            throw new \Exception('Export failed: '.$e->getMessage(), 0, $e);
        }
    }

    /**
     * Validate export request
     *
     * @throws \InvalidArgumentException
     */
    private function validateExportRequest(array $data, array $options)
    {
        // Check data size
        if (empty($data)) {
            throw new \InvalidArgumentException('No data to export');
        }

        // Limit export size (this should be configurable in your .env or config)
        $maxExportSize = config('exports.max_rows', 10000);
        if (count($data) > $maxExportSize) {
            throw new \InvalidArgumentException("Export exceeds maximum allowed rows ($maxExportSize)");
        }

        // Validate format
        $allowedFormats = ['xlsx', 'csv', 'pdf'];
        if (isset($options['format']) && ! in_array($options['format'], $allowedFormats)) {
            throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Apply data filters (support for searchQuery, date ranges, etc.)
     */
    private function applyDataFilters(array $data, array $filters): array
    {
        // Example: Apply search query filter
        if (isset($filters['searchQuery']) && $filters['searchQuery']) {
            $data = array_filter($data, function ($item) use ($filters) {
                foreach ($item as $key => $value) {
                    if (strpos(strtolower($value), strtolower($filters['searchQuery'])) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        // Apply date range filters if available
        if (isset($filters['startDate']) && isset($filters['endDate'])) {
            $data = array_filter($data, function ($item) use ($filters) {
                $date = strtotime($item['work_date'] ?? ''); // Adjust according to actual field
                $startDate = strtotime($filters['startDate']);
                $endDate = strtotime($filters['endDate']);

                return $date >= $startDate && $date <= $endDate;
            });
        }

        // Apply other filters (department, etc.)
        return array_filter($data, function ($item) use ($filters) {
            foreach ($filters as $key => $value) {
                if (isset($item[$key]) && $item[$key] != $value) {
                    return false;
                }
            }

            return true;
        });
    }

    /**
     * Select specific columns to export
     */
    private function selectColumns(array $data, ?array $columns): array
    {
        if (empty($columns)) {
            return $data;
        }

        // Only keep the specified columns
        return array_map(function ($item) use ($columns) {
            return array_intersect_key($item, array_flip($columns));
        }, $data);
    }

    /**
     * Perform export based on format (handle Excel, CSV, and PDF)
     *
     * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
     */
    private function performExport(array $data, array $options)
    {
        $filename = $options['filename'].'.'.$options['format'];

        switch ($options['format']) {
            case 'xlsx':
                return $this->exportExcel($data, $filename, $options);
            case 'csv':
                return $this->exportCSV($data, $filename, $options);
            case 'pdf':
                return $this->exportPDF($data, $filename, $options);
            default:
                throw new \InvalidArgumentException('Unsupported export format');
        }
    }

    /**
     * Export to Excel with advanced options
     */
    private function exportExcel(array $data, string $filename, array $options)
    {
        $export = Excel::download(
            new GenericDataExport($data, $options['columns']),
            $filename,
            \Maatwebsite\Excel\Excel::XLSX
        );

        // Handle password protection if specified
        if ($options['password']) {
            // Add password protection logic (e.g., using a library or Excel package)
        }

        return $export;
    }

    /**
     * Export to CSV with advanced options
     */
    private function exportCSV(array $data, string $filename, array $options)
    {
        return Excel::download(
            new GenericDataExport($data, $options['columns']),
            $filename,
            \Maatwebsite\Excel\Excel::CSV
        );
    }

    /**
     * Export to PDF with advanced styling
     */
    private function exportPDF(array $data, string $filename, array $options)
    {
        return Pdf::loadView('exports.advanced-pdf', [
            'data' => $data,
            'options' => $options,
        ])
            ->setPaper('a4')
            ->setOption('margin-top', 10)
            ->setOption('margin-bottom', 10)
            ->download($filename);
    }

    /**
     * Log export attempt
     */
    private function logExportAttempt(array $options, int $rowCount)
    {
        Log::info('Export Initiated', [
            'user_id' => auth()->id(),
            'format' => $options['format'],
            'filename' => $options['filename'],
            'row_count' => $rowCount,
            'timestamp' => now(),
        ]);
    }
}
