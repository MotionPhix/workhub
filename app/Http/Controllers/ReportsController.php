<?php

namespace App\Http\Controllers;

use App\Models\WorkEntry;
use App\Services\ProductivityInsightService;
use Illuminate\Http\Request;

class ReportsController extends Controller
{
  public function index()
  {
    $insightsService = new ProductivityInsightService();
    $insights = $insightsService->generateInsights(auth()->id());

    $workEntries = WorkEntry::where('user_id', auth()->id())
      ->latest()
      ->take(30)
      ->get();

    $chartData = $this->prepareChartData($workEntries);

    return response()->json([
      'chartData' => $chartData,
      'workEntries' => $workEntries,
      'insights' => $insights
    ]);
  }

  private function prepareChartData($entries)
  {
    return $entries->groupBy('work_date')->map(function ($group) {
      return [
        'date' => $group->first()->work_date,
        'total_hours' => $group->sum('hours_worked')
      ];
    })->values()->toArray();
  }
}
