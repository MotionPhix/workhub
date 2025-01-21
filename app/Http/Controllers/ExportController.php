<?php

namespace App\Http\Controllers;

use App\Models\WorkEntry;
use App\Services\ReportExportService;
use Illuminate\Http\Request;

class ExportController extends Controller
{
  protected $exportService;

  public function __construct(ReportExportService $exportService)
  {
    $this->exportService = $exportService;
  }

  public function export(Request $request)
  {
    // Apply filters (if any) to the WorkEntry model query
    $query = WorkEntry::query();

    // Apply filters like date range, department, or search query if provided
    if ($request->has('filters')) {
      $filters = $request->input('filters');

      // Example: Handle search query (assuming 'search' is a key in filters)
      if (isset($filters['search'])) {
        $query->where('description', 'like', '%' . $filters['search'] . '%');  // Adjust field based on your actual data
      }

      // Apply more filters (date, department, etc.) as needed
      if (isset($filters['start_date'])) {
        $query->where('work_date', '>=', $filters['start_date']);
      }

      if (isset($filters['end_date'])) {
        $query->where('work_date', '<=', $filters['end_date']);
      }

      if (isset($filters['department'])) {
        $query->whereHas('user', function ($q) use ($filters) {
          $q->where('department', $filters['department']);
        });
      }
    }

    // Fetch the data (paginate or get all records as per frontend requirements)
    $data = $query->get();  // Change to paginate if necessary

    // Prepare the options for the export service
    $options = [
      'filename' => $request->input('filename', 'work_entries_export'),
      'format' => $request->input('format', 'xlsx'),
      'columns' => $request->input('columns', []),  // Expected to be an array of visible columns
      'filters' => $filters,  // Pass along filters for further handling in export service
      'styling' => $request->input('styling', []),  // Optional styling (if needed)
    ];

    // Call the export service to handle the actual export logic
    return $this->exportService->advancedExport($data, $options);
  }
}
