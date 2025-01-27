<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\WorkEntry;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
  public function index(Request $request)
  {
    // Advanced reporting with filters
    $reports = WorkEntry::query()
      ->when($request->input('start_date'), function ($query, $startDate) {
        return $query->where('work_date', '>=', $startDate);
      })
      ->when($request->input('end_date'), function ($query, $endDate) {
        return $query->where('work_date', '<=', $endDate);
      })
      ->when($request->input('department'), function ($query, $department) {
        return $query->whereHas('user', function ($q) use ($department) {
          $q->where('department', $department);
        });
      })
      ->with('user')
      ->paginate(15);

    // Aggregate statistics
    $stats = [
      'total_hours' => $reports->sum('hours_worked'),
      'average_hours_per_day' => $reports->avg('hours_worked'),
      'departments' => DB::table('departments')
        ->select('name', DB::raw('count(*) as count'))
        ->groupBy('name')
        ->get()
    ];

    return Inertia('Reports/Index', [
      'reports' => $reports,
      'stats' => $stats,
      'filters' => $request->all()
    ]);
  }

  public function exportPDF(Request $request)
  {
    // Generate PDF export of reports
    $reports = WorkEntry::query()
      // Similar filtering as index method
      ->get();

    $pdf = PDF::loadView('reports.pdf', compact('reports'));
    return $pdf->download('work-report.pdf');
  }
}
