<?php

namespace App\Services;

use App\Exports\GenericDataExport;
use App\Exports\ProductivityExport;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ReportExportServiceOld
{
  /**
   * Export data using Laravel packages
   *
   * @param array $data Data to export
   * @param string $filename Filename for export
   * @param string $format Export format (xlsx, pdf, csv)
   * @return \Symfony\Component\HttpFoundation\BinaryFileResponse|\Illuminate\Http\Response
   */
  public function exportData(array $data, string $filename, string $format = 'xlsx')
  {
    // Prepare export data
    $exportData = $this->prepareExportData($data);

    switch ($format) {
      case 'xlsx':
        return Excel::download(new GenericDataExport($exportData), "{$filename}.xlsx");

      case 'csv':
        return Excel::download(new GenericDataExport($exportData), "{$filename}.csv");

      case 'pdf':
        return $this->generatePdfExport($exportData, $filename);

      default:
        throw new \InvalidArgumentException("Unsupported export format: {$format}");
    }
  }

  /**
   * Generate PDF export
   *
   * @param array $data
   * @param string $filename
   * @return \Illuminate\Http\Response
   */
  private function generatePdfExport(array $data, string $filename)
  {
    // Generate PDF with a view
    return Pdf::loadView('exports.generic-pdf', [
      'data' => $data,
      'filename' => $filename
    ])->download("{$filename}.pdf");
  }

  public function exportProductivityReport($userId, $format = 'pdf')
  {
    $insightService = new AdvancedProductivityInsightService();
    $insights = $insightService->generateComprehensiveInsights($userId);

    switch ($format) {
      case 'pdf':
        return $this->exportPDF($insights);
      case 'excel':
        return $this->exportExcel($insights);
      case 'json':
        return response()->json($insights);
      default:
        throw new \Exception('Unsupported export format');
    }
  }

  /**
   * Prepare data for export
   *
   * @param array $data
   * @return array
   */
  private function prepareExportData(array $data): array
  {
    // If data is empty, return empty array
    if (empty($data)) {
      return [];
    }

    // Get headers from first data item
    $headers = array_keys($data[0]);

    // Transform data to ensure compatibility with Excel export
    return array_map(function($item) use ($headers) {
      return array_combine($headers, array_map(function($header) use ($item) {
        // Handle nested objects or arrays
        return is_array($item[$header]) || is_object($item[$header])
          ? json_encode($item[$header])
          : $item[$header];
      }, $headers));
    }, $data);
  }

  private function exportPDF($insights)
  {
    $pdf = PDF::loadView('reports.productivity', ['insights' => $insights]);
    return $pdf->download('productivity_report.pdf');
  }

  private function exportExcel($insights)
  {
    return Excel::download(new ProductivityExport($insights), 'productivity_report.xlsx');
  }
}
