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

  /*public function export(Request $request)
  {
    $data = WorkEntry::where('user_id', auth()->id())->get()->toArray();

    return $this->exportService->exportData(
      $data,
      'work_entries_export',
      $request->input('format', 'xlsx')
    );
  }*/

  public function export(Request $request)
  {
    $data = WorkEntry::where('user_id', auth()->id())->get()->toArray();

    $options = [
      'filename' => $request->input('filename', 'work_entries_export'),
      'format' => $request->input('format', 'xlsx'),
      'columns' => $request->input('columns', null),
      'filters' => $request->input('filters', []),
      'styling' => $request->input('styling', []),
    ];

    return $this->exportService->advancedExport($data, $options);
  }
}
