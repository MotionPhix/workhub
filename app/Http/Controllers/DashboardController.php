<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\WorkEntry;
use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{

  public function __construct(protected DashboardService $dashboardService)
  {
  }

  public function index(Request $request)
  {
    $user = Auth::user();
    $stats = $this->getStatsByRole($user, $request);


    return Inertia('dashboard/Index', [
      'dashboard' => $stats
    ]);
  }

  private function getStatsByRole(User $user, Request $request): array
  {
    $baseStats = $this->dashboardService->getBaseStats($user->id);
    $timeFilter = $request->get('time_filter', 'month');

    if ($user->hasRole('admin')) {
      return array_merge(
        $baseStats,
        $this->dashboardService->getAdminStats(),
        [
          'time_filter' => $timeFilter,
          'system_metrics' => $this->dashboardService->getSystemMetrics($timeFilter),
          'organization_metrics' => $this->dashboardService->getOrganizationMetrics($timeFilter),
          'department_analytics' => $this->dashboardService->getDepartmentAnalytics(),
          'user_metrics' => $this->dashboardService->getUserMetrics(),
        ]
      );
    }

    if ($user->hasRole(['managing director', 'general manager'])) {
      return array_merge(
        $baseStats,
        $this->dashboardService->getManagerStats($user->email)
      );
    }

    return array_merge(
      $baseStats,
      $this->dashboardService->getEmployeeStats($user->id)
    );
  }

  /**
   * Update dashboard time filter
   */
  public function updateTimeFilter(Request $request)
  {
    $validated = $request->validate([
      'time_filter' => ['required', 'string', 'in:week,month,quarter,year'],
    ]);

    $user = Auth::user();

    if ($user->hasRole('admin')) {
      return [
        'organization_metrics' => $this->dashboardService->getOrganizationMetrics($validated['time_filter']),
        'system_metrics' => $this->dashboardService->getSystemMetrics($validated['time_filter']),
      ];
    }

    return response()->json([
      'message' => 'Unauthorized',
    ], 403);
  }


  /*public function index()
  {
    $user = Auth::user();

    // Common stats for both roles
    $commonData = [
      'total_work_entries' => WorkEntry::where('user_id', $user->id)->count(),
      'total_hours_worked' => WorkEntry::where('user_id', $user->id)->sum('hours_worked'),
      'recent_entries' => WorkEntry::where('user_id', $user->id)
        ->latest('work_date')
        ->limit(5)
        ->get(['uuid', 'work_date', 'work_title', 'hours_worked', 'status']),
    ];

    if ($user->hasRole('manager')) {
      // Manager-specific stats
      $teamUsers = User::where('manager_email', $user->email)->get();

      $managerData = [
        'team_size' => $teamUsers->count(),
        'team_hours_worked' => WorkEntry::whereIn('user_id', $teamUsers->pluck('id'))->sum('hours_worked'),
        'team_work_entries' => WorkEntry::whereIn('user_id', $teamUsers->pluck('id'))->count(),
        'top_performers' => $this->getTopPerformers($teamUsers),
        'recent_team_entries' => WorkEntry::whereIn('user_id', $teamUsers->pluck('id'))
          ->latest('work_date')
          ->limit(5)
          ->get(),
      ];

      return Inertia('dashboard/Index', array_merge($commonData, $managerData));
    }

    // Employee-specific insights
    $employeeData = [
      'productivity_insights' => $this->getProductivityInsights($user->id),
    ];

    return Inertia('dashboard/Index', array_merge($commonData, $employeeData));
  }

  private function getTopPerformers($teamUsers)
  {
    return WorkEntry::selectRaw('user_id, SUM(hours_worked) as total_hours')
      ->whereIn('user_id', $teamUsers->pluck('id'))
      ->groupBy('user_id')
      ->orderByDesc('total_hours')
      ->take(3)
      ->get()
      ->map(function ($entry) {
        return [
          'user' => User::find($entry->user_id),
          'total_hours' => $entry->total_hours,
        ];
      });
  }

  private function getProductivityInsights($userId)
  {
    $entries = WorkEntry::where('user_id', $userId)->latest()->limit(30)->get();

    return [
      'average_daily_hours' => $entries->avg('hours_worked'),
      'most_productive_days' => $this->getMostProductiveDays($entries),
      'productivity_trend' => $this->calculateProductivityTrend($entries),
      'recommended_work_hours' => round($entries->avg('hours_worked'), 1),
    ];
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

  private function calculateLinearTrend($data)
  {
    if ($data->isEmpty()) return 0;

    $keys = range(1, $data->count());
    $values = $data->values();
    $n = count($keys);
    $sumX = array_sum($keys);
    $sumY = $values->sum();
    $sumXY = collect($keys)->zip($values)->map(fn($pair) => $pair[0] * $pair[1])->sum();
    $sumXSquared = collect($keys)->map(fn($x) => $x * $x)->sum();
    $denominator = $n * $sumXSquared - $sumX * $sumX;

    return $denominator == 0 ? 0 : ($n * $sumXY - $sumX * $sumY) / $denominator;
  }*/
}
