<?php

namespace App\Services;

use App\Models\WorkEntry;
use Illuminate\Support\Facades\DB;

class ProductivityInsightService
{
  public function generateInsights($userId)
  {
    $entries = WorkEntry::where('user_id', $userId)
      ->latest()
      ->limit(30)
      ->get();

    // Basic predictive analysis
    $insights = [
      'average_daily_hours' => $entries->avg('hours_worked'),
      'most_productive_days' => $this->getMostProductiveDays($entries),
      'productivity_trend' => $this->calculateProductivityTrend($entries),
      'recommended_work_hours' => $this->recommendOptimalWorkHours($entries)
    ];

    return $insights;
  }

  private function getMostProductiveDays($entries)
  {
    return $entries
      ->groupBy('work_date')
      ->map(fn($group) => $group->sum('hours_worked'))
      ->sortDesc()
      ->take(3);
  }

  private function calculateProductivityTrend($entries)
  {
    $hoursPerDay = $entries->groupBy('work_date')
      ->map(fn($group) => $group->sum('hours_worked'));

    // Simple linear regression
    $trend = $this->calculateLinearTrend($hoursPerDay);

    return $trend > 0 ? 'Improving' : 'Declining';
  }

  private function recommendOptimalWorkHours($entries)
  {
    $averageHours = $entries->avg('hours_worked');
    return round($averageHours, 1);
  }

  private function calculateLinearTrend($data)
  {
    if ($data->isEmpty()) return 0;

    $n = $data->count();
    $sumX = $data->keys()->sum();
    $sumY = $data->sum();
    $sumXY = $data->keys()->zip($data)->map(fn($pair) => $pair[0] * $pair[1])->sum();
    $sumXSquared = $data->keys()->map(fn($x) => $x * $x)->sum();

    $slope = ($n * $sumXY - $sumX * $sumY) /
      ($n * $sumXSquared - $sumX * $sumX);

    return $slope;
  }
}
