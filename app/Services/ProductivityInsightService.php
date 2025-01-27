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

    // Convert the dates to numeric indices (e.g., sequential days)
    $keys = range(1, $data->count()); // Generate numeric indices (1, 2, 3, ...)
    $values = $data->values(); // Extract the work hours

    $n = count($keys);
    $sumX = array_sum($keys); // Sum of indices
    $sumY = $values->sum(); // Sum of work hours
    $sumXY = collect($keys)->zip($values)->map(fn($pair) => $pair[0] * $pair[1])->sum(); // Sum of x * y
    $sumXSquared = collect($keys)->map(fn($x) => $x * $x)->sum(); // Sum of x^2

    // Calculate denominator
    $denominator = $n * $sumXSquared - $sumX * $sumX;

    // Avoid division by zero
    if ($denominator == 0) {
      return 0; // Return 0 as the slope if there's no variance
    }

    // Calculate the slope
    $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;

    return $slope;
  }

}
