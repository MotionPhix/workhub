<?php

namespace App\Services;

use App\Models\User;
use App\Models\WorkEntry;
use App\Models\Department;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class DashboardService
{
  public function getBaseStats(int $userId): array
  {
    $currentMonth = Carbon::now()->startOfMonth();

    return [
      'base_metrics' => [
        'total_work_logs' => WorkEntry::where('user_id', $userId)->count(),
        'total_hours' => WorkEntry::where('user_id', $userId)->sum('hours_worked'),
        'average_hours_per_day' => $this->calculateAverageHours($userId),
        'completed_tasks' => WorkEntry::where('user_id', $userId)
          ->where('status', 'completed')
          ->count(),
      ],
      'recent_activity' => $this->getRecentActivity($userId),
    ];
  }

  private function getRecentActivity(int $userId): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);

    return [
      'recent_entries' => WorkEntry::where('user_id', $userId)
        ->where('work_date', '>=', $thirtyDaysAgo)
        ->latest('work_date')
        ->limit(5)
        ->get()
        ->map(function ($entry) {
          return [
            'id' => $entry->uuid,
            'date' => $entry->work_date->format('Y-m-d'),
            'title' => $entry->work_title,
            'hours' => $entry->hours_worked,
            'status' => $entry->status,
          ];
        })
        ->toArray(),

      'activity_summary' => [
        'total_entries_this_month' => WorkEntry::where('user_id', $userId)
          ->whereMonth('work_date', Carbon::now()->month)
          ->count(),

        'total_hours_this_month' => WorkEntry::where('user_id', $userId)
          ->whereMonth('work_date', Carbon::now()->month)
          ->sum('hours_worked'),

        'completion_rate' => $this->calculateCompletionRate($userId),

        'daily_average' => $this->calculateDailyAverage($userId),
      ]
    ];
  }

  /**
   * Get system metrics for the dashboard.
   */
  public function getSystemMetrics()
  {
    $totalUsers = User::count();
    $activeUsers = User::where('active', true)->count();
    $totalDepartments = Department::count();
    $verifiedUsersPercentage = User::where('verified', true)->count() / max($totalUsers, 1) * 100;

    return [
      'total_users' => $totalUsers,
      'active_users' => $activeUsers,
      'total_departments' => $totalDepartments,
      'verified_users_percentage' => $verifiedUsersPercentage,
    ];
  }

  /**
   * Get organization metrics for the dashboard.
   */
  public function getOrganizationMetrics()
  {
    $companyWideEfficiency = $this->calculateCompanyWideEfficiency();
    $totalProjects = Project::count();
    $activeProjects = Project::where('status', 'active')->count();
    $departmentPerformance = $this->getDepartmentPerformance();

    return [
      'company_wide_efficiency' => $companyWideEfficiency,
      'total_projects' => $totalProjects,
      'active_projects' => $activeProjects,
      'department_performance' => $departmentPerformance,
    ];
  }

  /**
   * Calculate company-wide efficiency.
   */
  private function calculateCompanyWideEfficiency()
  {
    // Example calculation logic
    $totalEfficiency = Department::sum('efficiency');
    $departmentCount = Department::count();

    return $departmentCount > 0 ? $totalEfficiency / $departmentCount : 0;
  }

  /**
   * Get department performance data.
   */
  private function getDepartmentPerformance()
  {
    return Department::select('name', 'efficiency', 'total_tasks', 'completed_tasks', 'total_hours', 'member_count')
      ->get()
      ->toArray();
  }

  /**
   * Get user metrics for the dashboard.
   */
  public function getUserMetrics()
  {
    $onboardingStatus = $this->getOnboardingStatus();
    $activityLogs = $this->getActivityLogs();
    $departmentDistribution = $this->getDepartmentDistribution();

    return [
      'onboarding_status' => $onboardingStatus,
      'activity_logs' => $activityLogs,
      'department_distribution' => $departmentDistribution,
    ];
  }

  /**
   * Get onboarding status metrics.
   */
  private function getOnboardingStatus()
  {
    $completed = UserOnboarding::whereNotNull('completed_at')->count();
    $pending = UserOnboarding::whereNull('completed_at')->count();
    $completionRate = $completed / max($completed + $pending, 1) * 100;

    return [
      'completed' => $completed,
      'pending' => $pending,
      'completion_rate' => $completionRate,
    ];
  }

  /**
   * Get activity logs metrics.
   */
  private function getActivityLogs()
  {
    $last24h = ActivityLog::where('created_at', '>=', now()->subDay())->count();
    $last7d = ActivityLog::where('created_at', '>=', now()->subDays(7))->count();
    $trend = $this->calculateActivityTrend($last24h, $last7d);

    return [
      'last_24h' => $last24h,
      'last_7d' => $last7d,
      'trend' => $trend,
    ];
  }

  /**
   * Calculate activity trend.
   */
  private function calculateActivityTrend($last24h, $last7d)
  {
    if ($last24h > $last7d / 7) {
      return 'increasing';
    } elseif ($last24h < $last7d / 7) {
      return 'decreasing';
    } else {
      return 'stable';
    }
  }

  /**
   * Get department distribution metrics.
   */
  private function getDepartmentDistribution()
  {
    return Department::withCount('users')
      ->get()
      ->map(function ($department) {
        $totalUsers = User::count();
        $percentage = $department->users_count / max($totalUsers, 1) * 100;

        return [
          'department_id' => $department->id,
          'count' => $department->users_count,
          'percentage' => $percentage,
        ];
      })
      ->toArray();
  }

  /**
   * Get department analytics for the dashboard.
   */
  public function getDepartmentAnalytics()
  {
    $resourceUtilization = $this->getResourceUtilization();
    $crossDepartmentCollaboration = $this->getCrossDepartmentCollaboration();
    $performanceTrends = $this->getPerformanceTrends();

    return [
      'resource_utilization' => $resourceUtilization,
      'cross_department_collaboration' => $crossDepartmentCollaboration,
      'performance_trends' => $performanceTrends,
    ];
  }

  /**
   * Get resource utilization metrics.
   */
  private function getResourceUtilization()
  {
    $allocatedHours = Department::sum('allocated_hours');
    $actualHours = Department::sum('actual_hours');
    $utilizationRate = $actualHours / max($allocatedHours, 1) * 100;

    return [
      'allocated_hours' => $allocatedHours,
      'actual_hours' => $actualHours,
      'utilization_rate' => $utilizationRate,
    ];
  }

  /**
   * Get cross-department collaboration metrics.
   */
  private function getCrossDepartmentCollaboration()
  {
    // Example logic for collaboration metrics
    $collaboratingDepartments = Department::whereHas('projects', function ($query) {
      $query->where('shared', true);
    })->pluck('name')->toArray();

    $sharedProjects = Project::where('shared', true)->count();
    $collaborationScore = $sharedProjects / max(count($collaboratingDepartments), 1) * 100;

    return [
      'collaborating_departments' => $collaboratingDepartments,
      'shared_projects' => $sharedProjects,
      'collaboration_score' => $collaborationScore,
    ];
  }

  /**
   * Get performance trends metrics.
   */
  private function getPerformanceTrends()
  {
    // Example logic for performance trends
    $weeklyEfficiency = Department::pluck('weekly_efficiency')->toArray();
    $monthlyCompletionRates = Department::pluck('monthly_completion_rate')->toArray();
    $yearOverYearGrowth = Department::sum('year_over_year_growth');

    return [
      'weekly_efficiency' => $weeklyEfficiency,
      'monthly_completion_rates' => $monthlyCompletionRates,
      'year_over_year_growth' => $yearOverYearGrowth,
    ];
  }

  private function calculateCompletionRate(int $userId): float
  {
    $thisMonth = Carbon::now()->startOfMonth();

    $entries = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', $thisMonth)
      ->get();

    $completedEntries = $entries->where('status', 'completed')->count();
    $totalEntries = $entries->count();

    return $totalEntries > 0
      ? round(($completedEntries / $totalEntries) * 100, 2)
      : 0;
  }

  private function calculateDailyAverage(int $userId): float
  {
    $thisMonth = Carbon::now()->startOfMonth();

    $workDays = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', $thisMonth)
      ->distinct('work_date')
      ->count();

    $totalHours = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', $thisMonth)
      ->sum('hours_worked');

    return $workDays > 0
      ? round($totalHours / $workDays, 2)
      : 0;
  }

  private function calculateVerifiedUsersPercentage(): float
  {
    $totalUsers = User::count();

    if ($totalUsers === 0) {
      return 0;
    }

    $verifiedUsers = User::whereNotNull('email_verified_at')->count();
    return round(($verifiedUsers / $totalUsers) * 100, 2);
  }

  private function calculateCompanyEfficiency(): float
  {
    $entries = WorkEntry::whereMonth('work_date', Carbon::now()->month)->get();

    if ($entries->isEmpty()) {
      return 0;
    }

    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();

    return round(($completedTasks / $totalTasks) * 100, 2);
  }

  private function getDepartmentPerformance(): array
  {
    return Department::with(['users', 'users.workEntries' => function ($query) {
      $query->whereMonth('work_date', Carbon::now()->month);
    }])
      ->get()
      ->map(function ($department) {
        $entries = $department->users->flatMap(function ($user) {
          return $user->workEntries;
        });

        $totalTasks = $entries->count();
        $completedTasks = $entries->where('status', 'completed')->count();
        $totalHours = $entries->sum('hours_worked');

        return [
          'name' => $department->name,
          'efficiency' => $totalTasks > 0
            ? round(($completedTasks / $totalTasks) * 100, 2)
            : 0,
          'total_tasks' => $totalTasks,
          'completed_tasks' => $completedTasks,
          'total_hours' => round($totalHours, 2),
          'member_count' => $department->users->count()
        ];
      })
      ->sortByDesc('efficiency')
      ->values()
      ->toArray();
  }

  public function getAdminStats(): array
  {
    return [
      'system_metrics' => [
        'total_users' => User::count(),
        'active_users' => User::active()->count(),
        'total_departments' => Department::count(),
        'verified_users_percentage' => $this->calculateVerifiedUsersPercentage(),
      ],
      'organization_metrics' => [
        'company_wide_efficiency' => $this->calculateCompanyEfficiency(),
        'total_projects' => WorkEntry::distinct('work_title')->count(),
        'active_projects' => WorkEntry::where('status', '!=', 'completed')
          ->distinct('work_title')
          ->count(),
        'department_performance' => $this->getDepartmentPerformance(),
      ],
      'activity_trends' => $this->getActivityTrends(),
    ];
  }

  private function calculateProductivityScore(int $userId): array
  {
    $thisMonth = Carbon::now()->startOfMonth();
    $entries = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', $thisMonth)
      ->get();

    // Calculate base metrics
    $totalHours = $entries->sum('hours_worked');
    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();
    $workDays = $entries->unique('work_date')->count();

    // Calculate various productivity indicators
    $hoursPerDay = $workDays > 0 ? $totalHours / $workDays : 0;
    $taskCompletionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;
    $consistencyScore = $this->calculateConsistencyScore($entries);

    // Calculate weighted productivity score
    $weightedScore = (
        ($hoursPerDay / 8) * 0.3 + // Weight for hours per day (normalized to 8 hours)
        ($taskCompletionRate / 100) * 0.4 + // Weight for task completion
        ($consistencyScore / 100) * 0.3 // Weight for consistency
      ) * 100;

    return [
      'score' => round($weightedScore, 2),
      'metrics' => [
        'hours_per_day' => round($hoursPerDay, 2),
        'task_completion_rate' => round($taskCompletionRate, 2),
        'consistency_score' => round($consistencyScore, 2)
      ],
      'breakdown' => [
        'total_hours' => round($totalHours, 2),
        'completed_tasks' => $completedTasks,
        'total_tasks' => $totalTasks,
        'work_days' => $workDays
      ]
    ];
  }

  private function calculateConsistencyScore(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    // Group entries by date and calculate daily hours
    $dailyHours = $entries->groupBy(function ($entry) {
      return $entry->work_date->format('Y-m-d');
    })->map->sum('hours_worked');

    // Calculate standard deviation of daily hours
    $mean = $dailyHours->average();
    $variance = $dailyHours->map(function ($hours) use ($mean) {
      return pow($hours - $mean, 2);
    })->average();

    $standardDeviation = sqrt($variance);

    // Calculate coefficient of variation (CV)
    $cv = $mean > 0 ? ($standardDeviation / $mean) : 1;

    // Convert CV to a 0-100 score (lower CV means higher consistency)
    // Using a sigmoid-like function to normalize the score
    $consistencyScore = 100 * (1 / (1 + exp($cv - 0.5)));

    return round($consistencyScore, 2);
  }

  private function analyzeWorkPattern(int $userId): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $entries = WorkEntry::where('user_id', $userId)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->get();

    // Group entries by day of week
    $dayPatterns = $entries->groupBy(function ($entry) {
      return $entry->work_date->format('l'); // Returns day name
    })->map(function ($dayEntries) {
      return [
        'average_hours' => round($dayEntries->avg('hours_worked'), 2),
        'total_entries' => $dayEntries->count(),
        'completion_rate' => $this->calculateDayCompletionRate($dayEntries)
      ];
    });

    // Calculate peak productivity times
    $timePatterns = $this->analyzePeakProductivityTimes($entries);

    return [
      'daily_patterns' => $dayPatterns,
      'peak_productivity' => $timePatterns,
      'average_hours' => round($entries->avg('hours_worked'), 2),
      'completion_rate' => $this->calculateCompletionRate($userId)
    ];
  }

  private function calculateDayCompletionRate(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $completed = $entries->where('status', 'completed')->count();
    return round(($completed / $entries->count()) * 100, 2);
  }

  private function analyzePeakProductivityTimes(Collection $entries): array
  {
    // Group entries by time of day (morning, afternoon, evening)
    $timeRanges = [
      'morning' => ['start' => 5, 'end' => 11],
      'afternoon' => ['start' => 12, 'end' => 16],
      'evening' => ['start' => 17, 'end' => 23]
    ];

    $productivityByTime = collect($timeRanges)->map(function ($range, $period) use ($entries) {
      $periodEntries = $entries->filter(function ($entry) use ($range) {
        $hour = $entry->created_at->hour;
        return $hour >= $range['start'] && $hour <= $range['end'];
      });

      if ($periodEntries->isEmpty()) {
        return ['hours' => 0, 'efficiency' => 0];
      }

      return [
        'hours' => round($periodEntries->sum('hours_worked'), 2),
        'efficiency' => $this->calculateTimeRangeEfficiency($periodEntries)
      ];
    });

    return $productivityByTime->toArray();
  }

  private function calculatePersonalCompletionRate(int $userId): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $currentMonth = Carbon::now()->startOfMonth();

    // Get entries for current month
    $monthlyEntries = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', Carbon::now()->month)
      ->get();

    // Get entries for last 30 days for trend analysis
    $thirtyDayEntries = WorkEntry::where('user_id', $userId)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->get();

    // Calculate monthly completion metrics
    $monthlyTotal = $monthlyEntries->count();
    $monthlyCompleted = $monthlyEntries->where('status', 'completed')->count();
    $monthlyRate = $monthlyTotal > 0 ? ($monthlyCompleted / $monthlyTotal) * 100 : 0;

    // Calculate weekly trends
    $weeklyTrends = $thirtyDayEntries
      ->groupBy(function ($entry) {
        return $entry->work_date->startOfWeek()->format('Y-m-d');
      })
      ->map(function ($weekEntries) {
        $total = $weekEntries->count();
        $completed = $weekEntries->where('status', 'completed')->count();
        return [
          'total' => $total,
          'completed' => $completed,
          'rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0
        ];
      });

    // Calculate completion time averages
    $completionTimes = $monthlyEntries
      ->where('status', 'completed')
      ->map(function ($entry) {
        return $entry->updated_at->diffInHours($entry->created_at);
      });

    $averageCompletionTime = $completionTimes->isNotEmpty()
      ? round($completionTimes->average(), 2)
      : 0;

    return [
      'current_month' => [
        'rate' => round($monthlyRate, 2),
        'completed' => $monthlyCompleted,
        'total' => $monthlyTotal,
        'average_completion_time' => $averageCompletionTime
      ],
      'weekly_trends' => $weeklyTrends->toArray(),
      'performance_indicator' => $this->calculatePerformanceIndicator($monthlyRate, $averageCompletionTime)
    ];
  }

  private function calculatePerformanceIndicator(float $completionRate, float $averageCompletionTime): string
  {
    // Base score on completion rate and time
    $score = ($completionRate * 0.7) - ($averageCompletionTime * 0.3);

    if ($score >= 80) {
      return 'Excellent';
    } elseif ($score >= 60) {
      return 'Good';
    } elseif ($score >= 40) {
      return 'Average';
    } else {
      return 'Needs Improvement';
    }
  }

  private function calculateTimeRangeEfficiency(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();
    $averageHours = $entries->avg('hours_worked');

    // Weighted score based on completion rate and work intensity
    $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) : 0;
    $workIntensity = min($averageHours / 4, 1); // Normalize to 4 hours

    return round(($completionRate * 0.7 + $workIntensity * 0.3) * 100, 2);
  }

  private function calculateProductivityTrend(int $userId): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $entries = WorkEntry::where('user_id', $userId)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->orderBy('work_date')
      ->get();

    // Group entries by week for trend analysis
    $weeklyMetrics = $entries->groupBy(function ($entry) {
      return $entry->work_date->startOfWeek()->format('Y-m-d');
    })->map(function ($weekEntries) {
      $totalHours = $weekEntries->sum('hours_worked');
      $completedTasks = $weekEntries->where('status', 'completed')->count();
      $totalTasks = $weekEntries->count();

      return [
        'hours' => round($totalHours, 2),
        'completion_rate' => $totalTasks > 0
          ? round(($completedTasks / $totalTasks) * 100, 2)
          : 0,
        'productivity_score' => $this->calculateWeeklyProductivityScore($weekEntries)
      ];
    });

    // Calculate trend direction and strength
    $productivityScores = $weeklyMetrics->pluck('productivity_score')->toArray();
    $trendAnalysis = $this->analyzeTrendDirection($productivityScores);

    return [
      'weekly_metrics' => $weeklyMetrics->toArray(),
      'trend_direction' => $trendAnalysis['direction'],
      'trend_strength' => $trendAnalysis['strength'],
      'trend_summary' => $this->generateTrendSummary($trendAnalysis),
      'comparison' => [
        'previous_period' => array_sum(array_slice($productivityScores, 0, 2)) / 2,
        'current_period' => array_sum(array_slice($productivityScores, -2)) / 2
      ]
    ];
  }

  private function calculateWeeklyProductivityScore(Collection $entries): float
  {
    $totalHours = $entries->sum('hours_worked');
    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();
    $uniqueDays = $entries->unique('work_date')->count();

    // Weighted scoring components
    $hoursScore = min($totalHours / (40 * 0.8), 1); // Normalized to 80% of 40-hour week
    $completionRate = $totalTasks > 0 ? ($completedTasks / $totalTasks) : 0;
    $consistencyScore = $uniqueDays / 5; // Normalized to 5-day work week

    // Calculate weighted score
    return round(
      ($hoursScore * 0.3 + $completionRate * 0.5 + $consistencyScore * 0.2) * 100,
      2
    );
  }

  private function analyzeTrendDirection(array $scores): array
  {
    if (empty($scores)) {
      return ['direction' => 'neutral', 'strength' => 0];
    }

    // Calculate linear regression
    $n = count($scores);
    $x = range(1, $n);
    $sumX = array_sum($x);
    $sumY = array_sum($scores);
    $sumXY = array_sum(array_map(function($i, $score) {
      return $i * $score;
    }, $x, $scores));
    $sumXX = array_sum(array_map(function($i) {
      return $i * $i;
    }, $x));

    // Calculate slope
    $slope = ($n * $sumXY - $sumX * $sumY) / ($n * $sumXX - $sumX * $sumX);

    // Determine direction and strength
    $direction = $slope > 0 ? 'improving' : ($slope < 0 ? 'declining' : 'neutral');
    $strength = abs($slope) * 10; // Normalize strength to 0-100 scale

    return [
      'direction' => $direction,
      'strength' => min(round($strength, 2), 100)
    ];
  }

  private function generateTrendSummary(array $trendAnalysis): string
  {
    $direction = $trendAnalysis['direction'];
    $strength = $trendAnalysis['strength'];

    if ($strength < 20) {
      return 'Productivity remains relatively stable';
    }

    $intensifier = $strength > 60 ? 'significantly ' :
      ($strength > 30 ? 'moderately ' : 'slightly ');

    return sprintf(
      'Productivity is %s%s',
      $intensifier,
      $direction === 'improving' ? 'improving' : 'declining'
    );
  }

  public function getManagerStats(string $managerEmail): array
  {
    $teamMembers = User::where('manager_email', $managerEmail)->get();
    $teamIds = $teamMembers->pluck('id');

    return [
      'team_performance' => [
        'total_team_members' => $teamMembers->count(),
        'team_average_hours' => $this->calculateTeamAverageHours($teamIds->toArray()),
        'top_performers' => $this->getTopPerformers($teamIds->toArray()),
        'team_efficiency' => $this->calculateTeamEfficiency($teamIds->toArray()),
      ],
      'department_metrics' => [
        'department_efficiency' => $this->calculateDepartmentEfficiency($teamIds->toArray()),
        'ongoing_projects' => $this->getOngoingProjects($teamIds->toArray()),
        'completion_rate' => $this->calculateTeamCompletionRate($teamIds->toArray()),
      ],
      'team_insights' => $this->getTeamInsights($teamIds->toArray()),
    ];
  }

  private function calculateTeamCompletionRate(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->whereMonth('work_date', $currentMonth)
      ->get();

    $completedEntries = $entries->where('status', 'completed')->count();
    $totalEntries = $entries->count();

    return [
      'rate' => $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100, 2) : 0,
      'completed' => $completedEntries,
      'total' => $totalEntries,
      'by_member' => $this->calculateMemberCompletionRates($teamIds)
    ];
  }

  private function calculateMemberCompletionRates(array $teamIds): array
  {
    return collect($teamIds)->mapWithKeys(function ($userId) {
      $entries = WorkEntry::where('user_id', $userId)
        ->whereMonth('work_date', Carbon::now()->month)
        ->get();

      $completed = $entries->where('status', 'completed')->count();
      $total = $entries->count();
      $user = User::find($userId);

      return [$userId => [
        'name' => $user->name,
        'rate' => $total > 0 ? round(($completed / $total) * 100, 2) : 0,
        'completed' => $completed,
        'total' => $total
      ]];
    })->toArray();
  }

  private function calculateDailyCollaborationIndex(Collection $entries, array $teamIds): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $uniqueMembers = $entries->pluck('user_id')->unique()->count();
    $memberRatio = $uniqueMembers / count($teamIds);

    $sharedWorkCount = $entries
      ->groupBy('work_title')
      ->filter(function ($group) {
        return $group->pluck('user_id')->unique()->count() > 1;
      })
      ->count();

    $collaborationIndex = ($memberRatio * 0.6) + ($sharedWorkCount / max($entries->count(), 1) * 0.4);
    return round($collaborationIndex * 100, 2);
  }

  private function calculateCommunicationScore(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->whereMonth('work_date', $currentMonth)
      ->get();

    return [
      'interaction_score' => $this->calculateTeamInteractionScore($entries, $teamIds),
      'response_time' => $this->calculateAverageResponseTime($teamIds),
      'collaboration_frequency' => $this->calculateCollaborationFrequency($entries)
    ];
  }

  private function calculateTeamInteractionScore(Collection $entries, array $teamIds): float
  {
    $totalPossibleInteractions = (count($teamIds) * (count($teamIds) - 1)) / 2;
    $actualInteractions = $entries
      ->groupBy('work_title')
      ->filter(function ($group) {
        return $group->pluck('user_id')->unique()->count() > 1;
      })
      ->count();

    return round(($actualInteractions / max($totalPossibleInteractions, 1)) * 100, 2);
  }

  private function calculateAverageResponseTime(array $teamIds): float
  {
    // Placeholder for actual response time calculation
    // This would typically involve analyzing communication timestamps
    // from a separate communications or notifications table
    return 0.0;
  }

  private function calculateCollaborationFrequency(Collection $entries): array
  {
    return [
      'daily' => $this->getCollaborationFrequencyByPeriod($entries, 'day'),
      'weekly' => $this->getCollaborationFrequencyByPeriod($entries, 'week'),
      'monthly' => $this->getCollaborationFrequencyByPeriod($entries, 'month')
    ];
  }

  private function getCollaborationFrequencyByPeriod(Collection $entries, string $period): float
  {
    $groupedEntries = $entries->groupBy(function ($entry) use ($period) {
      return $entry->work_date->startOf($period)->format('Y-m-d');
    });

    $collaborationCounts = $groupedEntries->map(function ($periodEntries) {
      return $periodEntries
        ->groupBy('work_title')
        ->filter(function ($group) {
          return $group->pluck('user_id')->unique()->count() > 1;
        })
        ->count();
    });

    return round($collaborationCounts->avg() ?? 0, 2);
  }

  private function getCrossDepartmentProjects(array $teamIds): array
  {
    $users = User::whereIn('id', $teamIds)->with('department')->get();
    $departmentIds = $users->pluck('department_uuid')->unique();

    return WorkEntry::whereIn('user_id', $teamIds)
      ->select('work_title')
      ->groupBy('work_title')
      ->get()
      ->map(function ($project) use ($teamIds) {
        $projectUsers = WorkEntry::where('work_title', $project->work_title)
          ->whereIn('user_id', $teamIds)
          ->with('user.department')
          ->get();

        $departments = $projectUsers->pluck('user.department_uuid')->unique();

        return [
          'project_name' => $project->work_title,
          'department_count' => $departments->count(),
          'departments' => Department::whereIn('uuid', $departments)->pluck('name'),
          'is_cross_department' => $departments->count() > 1
        ];
      })
      ->filter(function ($project) {
        return $project['is_cross_department'];
      })
      ->values()
      ->toArray();
  }

  private function calculateDepartmentCollaborationScore(array $teamIds): float
  {
    $users = User::whereIn('id', $teamIds)->with('department')->get();
    $departments = $users->pluck('department_uuid')->unique();

    if ($departments->count() <= 1) {
      return 0;
    }

    $crossDeptProjects = $this->getCrossDepartmentProjects($teamIds);
    $totalProjects = WorkEntry::whereIn('user_id', $teamIds)
      ->distinct('work_title')
      ->count();

    return $totalProjects > 0
      ? round((count($crossDeptProjects) / $totalProjects) * 100, 2)
      : 0;
  }

  private function calculateProjectCompletionRate(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $completed = $entries->where('status', 'completed')->count();
    return round(($completed / $entries->count()) * 100, 2);
  }

  private function identifyImprovementAreas(Collection $entries, array $teamIds): array
  {
    $areas = [];

    // Check workload distribution
    $workloadDistribution = $this->analyzeWorkloadDistribution($teamIds);
    $workloadVariance = $this->calculateWorkloadVariance($workloadDistribution);

    if ($workloadVariance > 0.3) {
      $areas[] = [
        'area' => 'Workload Distribution',
        'description' => 'High variance in team workload distribution',
        'recommendation' => 'Consider redistributing tasks more evenly among team members'
      ];
    }

    // Check completion rates
    $completionRate = $this->calculateTeamCompletionRate($teamIds)['rate'];
    if ($completionRate < 70) {
      $areas[] = [
        'area' => 'Task Completion',
        'description' => 'Below target task completion rate',
        'recommendation' => 'Review task allocation and identify bottlenecks'
      ];
    }

    // Check collaboration levels
    $collaborationMetrics = $this->getCollaborationMetrics($teamIds);
    if ($collaborationMetrics['team_interaction']['project_collaboration']['average_collaboration_score'] < 50) {
      $areas[] = [
        'area' => 'Team Collaboration',
        'description' => 'Low team collaboration score',
        'recommendation' => 'Encourage more cross-member project work'
      ];
    }

    return $areas;
  }

  private function calculateWorkloadVariance(array $workloadDistribution): float
  {
    // Handle empty array case
    if (empty($workloadDistribution)) {
      return 0.0;
    }

    $scores = collect($workloadDistribution)->pluck('workload_score');

    // Handle empty scores or all zero scores
    if ($scores->isEmpty() || $scores->sum() === 0) {
      return 0.0;
    }

    $mean = $scores->avg();

    // Prevent division by zero if mean is 0
    if ($mean === 0) {
      return 0.0;
    }

    $variance = $scores->map(function ($score) use ($mean) {
      return pow($score - $mean, 2);
    })->avg() ?? 0.0; // Use null coalescing operator to handle null average

    // Calculate coefficient of variation with safety check
    return $variance > 0 ? sqrt($variance) / $mean : 0.0;
  }

  private function getTeamAchievements(Collection $entries, array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $previousMonth = Carbon::now()->subMonth()->startOfMonth();

    $currentMetrics = $this->calculateMonthlyMetrics($entries);
    $previousMetrics = $this->calculateMonthlyMetrics(
      WorkEntry::whereIn('user_id', $teamIds)
        ->whereMonth('work_date', $previousMonth)
        ->get()
    );

    $achievements = [];

    // Productivity improvements
    if ($currentMetrics['productivity'] > $previousMetrics['productivity']) {
      $improvement = round($currentMetrics['productivity'] - $previousMetrics['productivity'], 2);
      $achievements[] = [
        'type' => 'Productivity',
        'description' => "Improved team productivity by {$improvement}%",
        'impact' => 'high'
      ];
    }

    // Completion rate improvements
    if ($currentMetrics['completion_rate'] > $previousMetrics['completion_rate']) {
      $improvement = round($currentMetrics['completion_rate'] - $previousMetrics['completion_rate'], 2);
      $achievements[] = [
        'type' => 'Task Completion',
        'description' => "Increased task completion rate by {$improvement}%",
        'impact' => 'medium'
      ];
    }

    // Add project-specific achievements
    $achievements = array_merge(
      $achievements,
      $this->getProjectAchievements($entries)
    );

    return $achievements;
  }

  private function calculateMonthlyMetrics(Collection $entries): array
  {
    if ($entries->isEmpty()) {
      return [
        'productivity' => 0,
        'completion_rate' => 0,
        'average_hours' => 0
      ];
    }

    return [
      'productivity' => round($entries->sum('hours_worked') / $entries->unique('work_date')->count(), 2),
      'completion_rate' => round(($entries->where('status', 'completed')->count() / $entries->count()) * 100, 2),
      'average_hours' => round($entries->avg('hours_worked'), 2)
    ];
  }

  private function getProjectAchievements(Collection $entries): array
  {
    return $entries
      ->where('status', 'completed')
      ->groupBy('work_title')
      ->map(function ($projectEntries) {
        $averageCompletionTime = $projectEntries->avg(function ($entry) {
          return $entry->updated_at->diffInHours($entry->created_at);
        });

        return [
          'type' => 'Project Completion',
          'description' => "Completed project '{$projectEntries->first()->work_title}'",
          'impact' => $this->determineProjectImpact($averageCompletionTime, $projectEntries->count())
        ];
      })
      ->values()
      ->toArray();
  }

  private function determineProjectImpact(float $completionTime, int $taskCount): string
  {
    $score = ($taskCount * 10) / max($completionTime, 1);

    if ($score >= 8) {
      return 'high';
    } elseif ($score >= 5) {
      return 'medium';
    }
    return 'low';
  }

  // Make sure all array conversions are consistent
  private function getTeamInsights(array $teamIds): array
  {
    return [
      'workload_distribution' => $this->analyzeWorkloadDistribution($teamIds),
      'collaboration_metrics' => $this->getCollaborationMetrics($teamIds),
      'performance_summary' => $this->getTeamPerformanceSummary($teamIds)
    ];
  }

  private function calculateTeamAverageHours(array $teamIds): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->get();

    return [
      'daily_average' => round($entries->avg('hours_worked') ?? 0, 2),
      'monthly_total' => round($entries->sum('hours_worked') ?? 0, 2),
      'per_member_average' => $this->calculatePerMemberAverages($entries, $teamIds),
      'trend' => $this->calculateTeamHoursTrend($entries)
    ];
  }

  private function calculatePerMemberAverages(Collection $entries, array $teamIds): array
  {
    return $entries->groupBy('user_id')
      ->map(function ($userEntries) {
        return [
          'hours' => round($userEntries->sum('hours_worked'), 2),
          'daily_average' => round($userEntries->avg('hours_worked'), 2),
          'days_worked' => $userEntries->unique('work_date')->count()
        ];
      })
      ->toArray();
  }

  private function calculateTeamHoursTrend(Collection $entries): array
  {
    $weeklyHours = $entries->groupBy(function ($entry) {
      return $entry->work_date->startOfWeek()->format('Y-m-d');
    })
      ->map(function ($weekEntries) {
        return round($weekEntries->sum('hours_worked'), 2);
      });

    $trend = $this->analyzeTrendDirection($weeklyHours->values()->toArray());

    return [
      'weekly_hours' => $weeklyHours->toArray(),
      'direction' => $trend['direction'],
      'strength' => $trend['strength']
    ];
  }

  private function calculateTeamEfficiency(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->whereMonth('work_date', $currentMonth)
      ->get();

    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();
    $efficiency = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

    return [
      'overall_efficiency' => round($efficiency, 2),
      'completed_tasks' => $completedTasks,
      'total_tasks' => $totalTasks,
      'member_efficiency' => $this->calculateMemberEfficiency($teamIds)
    ];
  }

  private function getCollaborationMetrics(array $teamIds): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->get();

    return [
      'shared_projects' => $this->analyzeSharedProjects($teamIds),
      'cross_department_work' => $this->analyzeCrossDepartmentWork($teamIds),
      'team_interaction' => [
        'project_collaboration' => $this->calculateProjectCollaboration($entries, $teamIds),
        'communication_score' => $this->calculateCommunicationScore($teamIds),
      ],
      'collaboration_trends' => $this->getCollaborationTrends($entries, $teamIds)
    ];
  }

  private function analyzeSharedProjects(array $teamIds): array
  {
    return WorkEntry::whereIn('user_id', $teamIds)
      ->select('work_title')
      ->groupBy('work_title')
      ->havingRaw('COUNT(DISTINCT user_id) > 1')
      ->get()
      ->map(function ($project) use ($teamIds) {
        $projectEntries = WorkEntry::whereIn('user_id', $teamIds)
          ->where('work_title', $project->work_title)
          ->get();

        return [
          'project_name' => $project->work_title,
          'member_count' => $projectEntries->unique('user_id')->count(),
          'total_hours' => round($projectEntries->sum('hours_worked'), 2),
          'completion_rate' => $this->calculateProjectCompletionRate($projectEntries)
        ];
      })
      ->toArray();
  }

  private function analyzeCrossDepartmentWork(array $teamIds): array
  {
    $users = User::whereIn('id', $teamIds)->get();
    $departments = $users->pluck('department_uuid')->unique();

    return [
      'department_count' => $departments->count(),
      'cross_dept_projects' => $this->getCrossDepartmentProjects($teamIds),
      'department_collaboration_score' => $this->calculateDepartmentCollaborationScore($teamIds)
    ];
  }

  private function calculateProjectCollaboration(Collection $entries, array $teamIds): array
  {
    $collaborationData = $entries
      ->groupBy('work_title')
      ->map(function ($projectEntries) use ($teamIds) {
        $uniqueMembers = $projectEntries->pluck('user_id')->unique();
        $collaborationScore = ($uniqueMembers->count() / count($teamIds)) * 100;

        return [
          'member_count' => $uniqueMembers->count(),
          'collaboration_score' => round($collaborationScore, 2),
          'total_hours' => round($projectEntries->sum('hours_worked'), 2)
        ];
      });

    return [
      'average_collaboration_score' => round($collaborationData->avg('collaboration_score'), 2),
      'most_collaborative_projects' => $collaborationData
        ->sortByDesc('collaboration_score')
        ->take(5)
        ->toArray()
    ];
  }

  private function getCollaborationTrends(Collection $entries, array $teamIds): array
  {
    return $entries
      ->groupBy(function ($entry) {
        return $entry->work_date->format('Y-m-d');
      })
      ->map(function ($dayEntries) use ($teamIds) {
        $activeMembers = $dayEntries->pluck('user_id')->unique()->count();
        $sharedProjects = $dayEntries
          ->groupBy('work_title')
          ->filter(function ($projectEntries) {
            return $projectEntries->pluck('user_id')->unique()->count() > 1;
          })
          ->count();

        return [
          'date' => $dayEntries->first()->work_date->format('Y-m-d'),
          'active_members' => $activeMembers,
          'shared_projects' => $sharedProjects,
          'collaboration_index' => $this->calculateDailyCollaborationIndex($dayEntries, $teamIds)
        ];
      })
      ->values()
      ->toArray();
  }

  private function getTeamPerformanceSummary(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->whereMonth('work_date', $currentMonth)
      ->get();

    return [
      'overall_metrics' => [
        'productivity_score' => $this->calculateTeamProductivityScore($entries, $teamIds),
        'efficiency_rate' => $this->calculateTeamEfficiencyRate($entries),
        'quality_score' => $this->calculateTeamQualityScore($entries)
      ],
      'performance_trends' => $this->analyzeTeamPerformanceTrends($teamIds),
      'improvement_areas' => $this->identifyImprovementAreas($entries, $teamIds),
      'achievements' => $this->getTeamAchievements($entries, $teamIds)
    ];
  }

  private function calculateTeamProductivityScore(Collection $entries, array $teamIds): float
  {
    $totalPossibleHours = count($teamIds) * 8 * $entries->unique('work_date')->count();
    $actualHours = $entries->sum('hours_worked');
    $completionRate = $entries->where('status', 'completed')->count() / max($entries->count(), 1);

    $productivityScore = ($actualHours / max($totalPossibleHours, 1)) * 0.6 + $completionRate * 0.4;
    return round($productivityScore * 100, 2);
  }

  private function calculateTeamEfficiencyRate(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $completedTasks = $entries->where('status', 'completed');
    $averageCompletionTime = $completedTasks->avg(function ($entry) {
      return $entry->updated_at->diffInHours($entry->created_at);
    }) ?: 0;

    $efficiency = max(0, 100 - ($averageCompletionTime * 2));
    return round($efficiency, 2);
  }

  private function calculateTeamQualityScore(Collection $entries): float
  {
    if ($entries->isEmpty()) {
      return 0;
    }

    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();
    $onTimeCompletions = $entries->where('status', 'completed')
      ->filter(function ($entry) {
        return $entry->updated_at->diffInHours($entry->created_at) <= 24;
      })
      ->count();

    $qualityScore = ($completedTasks / $totalTasks * 0.6) + ($onTimeCompletions / max($completedTasks, 1) * 0.4);
    return round($qualityScore * 100, 2);
  }

  private function analyzeTeamPerformanceTrends(array $teamIds): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);
    $entries = WorkEntry::whereIn('user_id', $teamIds)
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->get();

    return $entries
      ->groupBy(function ($entry) {
        return $entry->work_date->format('Y-m-d');
      })
      ->map(function ($dayEntries) use ($teamIds) {
        return [
          'date' => $dayEntries->first()->work_date->format('Y-m-d'),
          'productivity_score' => $this->calculateTeamProductivityScore($dayEntries, $teamIds),
          'efficiency_rate' => $this->calculateTeamEfficiencyRate($dayEntries),
          'quality_score' => $this->calculateTeamQualityScore($dayEntries)
        ];
      })
      ->values()
      ->toArray();
  }

  private function calculateMemberEfficiency(array $teamIds): array
  {
    return collect($teamIds)->mapWithKeys(function ($userId) {
      $efficiency = $this->calculateEfficiencyScore($userId);
      $user = User::find($userId);

      return [$userId => [
        'name' => $user->name,
        'efficiency' => $efficiency,
        'avatar' => $user->avatar
      ]];
    })->toArray();
  }

  private function calculateDepartmentEfficiency(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();

    // Get all department IDs for the team
    $departmentIds = User::whereIn('id', $teamIds)
      ->pluck('department_uuid')
      ->unique();

    return Department::whereIn('uuid', $departmentIds)
      ->get()
      ->mapWithKeys(function ($department) {
        $departmentUsers = User::where('department_uuid', $department->uuid)->pluck('id');
        $entries = WorkEntry::whereIn('user_id', $departmentUsers)
          ->whereMonth('work_date', Carbon::now()->month)
          ->get();

        $completedTasks = $entries->where('status', 'completed')->count();
        $totalTasks = $entries->count();
        $efficiency = $totalTasks > 0 ? ($completedTasks / $totalTasks) * 100 : 0;

        return [$department->uuid => [
          'name' => $department->name,
          'efficiency' => round($efficiency, 2),
          'total_tasks' => $totalTasks,
          'completed_tasks' => $completedTasks
        ]];
      })
      ->toArray();
  }

  private function getOngoingProjects(array $teamIds): array
  {
    return WorkEntry::whereIn('user_id', $teamIds)
      ->where('status', '!=', 'completed')
      ->distinct('work_title')
      ->select('work_title', DB::raw('COUNT(*) as task_count'))
      ->groupBy('work_title')
      ->orderByDesc('task_count')
      ->get()
      ->map(function ($project) use ($teamIds) {
        return [
          'title' => $project->work_title,
          'task_count' => $project->task_count,
          'assigned_members' => $this->getProjectMembers($project->work_title, $teamIds)
        ];
      })
      ->toArray();
  }

  private function getProjectMembers(string $projectTitle, array $teamIds): array
  {
    return WorkEntry::whereIn('user_id', $teamIds)
      ->where('work_title', $projectTitle)
      ->distinct('user_id')
      ->get()
      ->map(function ($entry) {
        $user = User::find($entry->user_id);
        return [
          'id' => $user->id,
          'name' => $user->name,
          'avatar' => $user->avatar
        ];
      })
      ->toArray();
  }

  private function analyzeWorkloadDistribution(array $teamIds): array
  {
    $currentMonth = Carbon::now()->startOfMonth();

    return User::whereIn('id', $teamIds)
      ->get()
      ->mapWithKeys(function ($user) {
        $entries = WorkEntry::where('user_id', $user->id)
          ->whereMonth('work_date', Carbon::now()->month)
          ->get();

        return [$user->id => [
          'name' => $user->name,
          'total_hours' => round($entries->sum('hours_worked'), 2),
          'task_count' => $entries->count(),
          'workload_score' => $this->calculateWorkloadScore($entries)
        ]];
      })
      ->toArray();
  }

  private function calculateWorkloadScore(Collection $entries): float
  {
    $totalHours = $entries->sum('hours_worked');
    $taskCount = $entries->count();
    $uniqueDays = $entries->unique('work_date')->count();

    // Normalize scores between 0 and 1
    $hoursScore = min($totalHours / (40 * 0.8), 1); // 80% of 40-hour week
    $taskScore = min($taskCount / 20, 1); // Normalize to 20 tasks
    $consistencyScore = $uniqueDays / 20; // Normalize to 20 working days

    return round(
      ($hoursScore * 0.4 + $taskScore * 0.4 + $consistencyScore * 0.2) * 100,
      2
    );
  }

  public function getEmployeeStats(int $userId): array
  {
    return [
      'personal_metrics' => [
        'productivity_score' => $this->calculateProductivityScore($userId),
        'completion_rate' => $this->calculatePersonalCompletionRate($userId),
        'work_pattern' => $this->analyzeWorkPattern($userId),
      ],
      'performance_insights' => [
        'trend' => $this->calculateProductivityTrend($userId),
        'recommendations' => $this->generateRecommendations($userId),
      ],
    ];
  }

  private function calculateAverageHours(int $userId): float
  {
    return round(WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', Carbon::now()->month)
      ->avg('hours_worked') ?? 0, 2);
  }

  private function getTopPerformers(array $teamIds): array
  {
    return WorkEntry::selectRaw('user_id, SUM(hours_worked) as total_hours')
      ->whereIn('user_id', $teamIds)
      ->whereMonth('work_date', Carbon::now()->month)
      ->groupBy('user_id')
      ->orderByDesc('total_hours')
      ->limit(5)
      ->get()
      ->map(function ($entry) {
        $user = User::find($entry->user_id);
        return [
          'name' => $user->name,
          'hours' => round($entry->total_hours, 2),
          'avatar' => $user->avatar,
          'efficiency' => $this->calculateEfficiencyScore($user->id),
        ];
      })
      ->toArray();
  }

  private function calculateEfficiencyScore(int $userId): float
  {
    $entries = WorkEntry::where('user_id', $userId)
      ->whereMonth('work_date', Carbon::now()->month)
      ->get();

    $completedTasks = $entries->where('status', 'completed')->count();
    $totalTasks = $entries->count();

    return $totalTasks > 0
      ? round(($completedTasks / $totalTasks) * 100, 2)
      : 0;
  }

  private function getActivityTrends(): array
  {
    $thirtyDaysAgo = Carbon::now()->subDays(30);

    return WorkEntry::select(
      DB::raw('DATE(work_date) as date'),
      DB::raw('COUNT(*) as total_entries'),
      DB::raw('SUM(hours_worked) as total_hours')
    )
      ->where('work_date', '>=', $thirtyDaysAgo)
      ->groupBy('date')
      ->orderBy('date')
      ->get()
      ->map(function ($entry) {
        return [
          'date' => $entry->date,
          'entries' => $entry->total_entries,
          'hours' => round($entry->total_hours, 2),
        ];
      })
      ->toArray();
  }

  private function generateRecommendations(int $userId): array
  {
    $workPattern = $this->analyzeWorkPattern($userId);
    $recommendations = [];

    if ($workPattern['average_hours'] < 6) {
      $recommendations[] = 'Consider increasing daily work hours for better productivity';
    }

    if ($workPattern['completion_rate'] < 0.7) {
      $recommendations[] = 'Focus on completing existing tasks before taking new ones';
    }

    return $recommendations;
  }
}
