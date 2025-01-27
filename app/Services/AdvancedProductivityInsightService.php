<?php

namespace App\Services;

use App\Models\WorkEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Phpml\Regression\LeastSquares;
use Phpml\FeatureExtraction\TokenCountVectorizer;
use Phpml\Tokenization\WhitespaceTokenizer;

class AdvancedProductivityInsightService
{
  public function generateComprehensiveInsights($userId)
  {
    $entries = WorkEntry::where('user_id', $userId)
      ->latest()
      ->limit(90)
      ->get();

    return [
      'productivity_score' => $this->calculateProductivityScore($entries),
      'energy_patterns' => $this->analyzeEnergyPatterns($entries),
      'task_complexity_correlation' => $this->correlateTaskComplexity($entries),
      'predictive_productivity' => $this->predictFutureProductivity($entries),
      'burnout_risk' => $this->assessBurnoutRisk($entries),
      'focus_time_analysis' => $this->analyzeFocusTime($entries)
    ];
  }

  private function calculateProductivityScore($entries)
  {
    // Complex scoring mechanism
    $hoursWorked = $entries->sum('hours_worked');
    $uniqueProjects = $entries->pluck('project')->unique()->count();
    $consistencyFactor = $this->calculateConsistency($entries);

    return round(
      ($hoursWorked * 0.4) +
      ($uniqueProjects * 5) +
      ($consistencyFactor * 10),
      2
    );
  }

  private function analyzeEnergyPatterns($entries)
  {
    $hourlyProductivity = $entries->groupBy(function ($entry) {
      return \Carbon\Carbon::parse($entry->created_at)->hour;
    })->map(function ($group) {
      return $group->avg('hours_worked');
    });

    return [
      'most_productive_hours' => $hourlyProductivity->sort()->reverse()->take(3),
      'least_productive_hours' => $hourlyProductivity->sort()->take(3)
    ];
  }

  private function correlateTaskComplexity($entries)
  {
    // Use machine learning to correlate task descriptions with productivity
    $vectorizer = new TokenCountVectorizer(new WhitespaceTokenizer());

    $samples = $entries->map(function ($entry) use ($vectorizer) {

      $vectorizer->fit([$entry->description]);
      $arr = [$entry->description];

      $vectorizer->transform($arr);

      return [
        'tokens' => $vectorizer->getVocabulary(),
        'hours_worked' => $entry->hours_worked
      ];

    });

    return $samples;
  }

  private function predictFutureProductivity($entries): array
  {
    try {
      // Ensure we have entries to process
      if ($entries->isEmpty()) {
        return array_fill(0, 7, 0.0);
      }

      // Prepare training data
      $samples = [];
      $targets = [];

      // Convert Collection to properly formatted training data
      foreach ($entries as $index => $entry) {
        $hoursWorked = (float)$entry->hours_worked;

        // Skip invalid entries
        if ($hoursWorked <= 0) {
          continue;
        }

        // Create sample arrays for each entry
        $samples[] = [$index + 1]; // Add as array for LeastSquares requirement
        $targets[] = $hoursWorked;
      }

      // Check if we have enough data points
      if (count($samples) < 2) {
        return array_fill(0, 7, array_sum($targets) / max(count($targets), 1));
      }

      // Initialize and train regression model
      $regression = new LeastSquares();
      $regression->train($samples, $targets);

      // Generate predictions for next 7 days
      $predictions = [];
      $lastIndex = count($samples) + 1;

      for ($i = 0; $i < 7; $i++) {
        try {
          $prediction = $regression->predict([$lastIndex + $i]);
          // Ensure prediction is non-negative
          $predictions[] = max(0, round($prediction, 2));
        } catch (\Exception $e) {
          // Fallback to average if prediction fails
          $predictions[] = round(array_sum($targets) / count($targets), 2);
        }
      }

      return $predictions;
    } catch (\Exception $e) {
      // Return zero-filled array if anything fails
      Log::error('Productivity prediction failed: ' . $e->getMessage());
      return array_fill(0, 7, 0.0);
    }
  }

  private function assessBurnoutRisk($entries)
  {
    $recentEntries = $entries->take(30);

    $averageHoursPerDay = $recentEntries->avg('hours_worked');
    $varianceInHours = $recentEntries->variance('hours_worked');
    $consecutiveLongDays = $this->countConsecutiveLongWorkDays($recentEntries);

    $burnoutScore =
      ($averageHoursPerDay > 8 ? 1 : 0) +
      ($varianceInHours > 2 ? 1 : 0) +
      ($consecutiveLongDays > 5 ? 1 : 0);

    return [
      'risk_level' => $this->getBurnoutRiskLevel($burnoutScore),
      'score' => $burnoutScore
    ];
  }

  private function analyzeFocusTime($entries)
  {
    // Analyze potential focus and deep work time
    $focusTimeEntries = $entries->filter(function ($entry) {
      return $entry->hours_worked > 3; // Potential deep work sessions
    });

    return [
      'total_focus_hours' => $focusTimeEntries->sum('hours_worked'),
      'average_focus_session' => $focusTimeEntries->avg('hours_worked')
    ];
  }

  private function getBurnoutRiskLevel($score)
  {
    return match (true) {
      $score >= 2 => 'High Risk',
      $score === 1 => 'Moderate Risk',
      default => 'Low Risk'
    };
  }

  private function calculateConsistency($entries)
  {
    if ($entries->isEmpty()) return 0;

    $totalEntries = $entries->count();
    $entriesWithWork = $entries->filter(fn($entry) => $entry->hours_worked > 0)->count();

    return round(($entriesWithWork / $totalEntries) * 100, 2);
  }

  private function countConsecutiveLongWorkDays($entries)
  {
    $longDays = 0;
    $consecutiveCount = 0;

    foreach ($entries as $entry) {
      if ($entry->hours_worked > 8) {
        $consecutiveCount++;
        $longDays = max($longDays, $consecutiveCount);
      } else {
        $consecutiveCount = 0;
      }
    }

    return $longDays;
  }

  /**
   * Calculate variance for a collection of numbers
   *
   * @param Collection $numbers
   * @return float
   */
  private function calculateVariance(Collection $numbers): float
  {
    $mean = $numbers->avg();
    $squaredDiffs = $numbers->map(fn($value) => pow($value - $mean, 2));
    return $squaredDiffs->avg() ?? 0;
  }

  /**
   * Get team burnout risk analysis
   *
   * @param array $filters
   * @return array
   */
  public function getTeamBurnoutRisks(array $filters): array
  {
    $teamEntries = WorkEntry::query()
      ->when($filters['start_date'] ?? null, fn($q) => $q->where('work_date', '>=', $filters['start_date']))
      ->when($filters['end_date'] ?? null, fn($q) => $q->where('work_date', '<=', $filters['end_date']))
      ->when($filters['department'] ?? null, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('department', $filters['department']))
      )
      ->with('user')
      ->get()
      ->groupBy('user_id');

    $risks = $teamEntries->map(function ($userEntries) {
      $user = $userEntries->first()->user;

      // Calculate average daily hours
      $averageHours = $userEntries->avg('hours_worked');

      // Calculate work pattern consistency
      $hoursVariance = $this->calculateVariance($userEntries->pluck('hours_worked'));

      // Count consecutive long days
      $consecutiveLongDays = $this->countConsecutiveLongWorkDays($userEntries);

      // Calculate weekend work frequency
      $weekendWorkFrequency = $userEntries
          ->filter(fn($entry) => $entry->work_date->isWeekend())
          ->count() / max($userEntries->count(), 1);

      // Calculate risk factors
      $riskFactors = [
        'high_hours' => $averageHours > 9 ? 1 : 0,
        'inconsistent_pattern' => $hoursVariance > 4 ? 1 : 0,
        'consecutive_long_days' => $consecutiveLongDays > 5 ? 1 : 0,
        'weekend_work' => $weekendWorkFrequency > 0.3 ? 1 : 0
      ];

      $riskScore = array_sum($riskFactors);

      return [
        'user_id' => $user->id,
        'name' => $user->name,
        'risk_level' => $this->calculateRiskLevel($riskScore),
        'risk_score' => $riskScore,
        'factors' => [
          'average_daily_hours' => round($averageHours, 2),
          'work_pattern_variance' => round($hoursVariance, 2),
          'consecutive_long_days' => $consecutiveLongDays,
          'weekend_work_frequency' => round($weekendWorkFrequency * 100, 2)
        ],
        'recommendations' => $this->generateBurnoutRecommendations($riskFactors)
      ];
    });

    return [
      'team_risks' => $risks->values()->toArray(),
      'summary' => [
        'high_risk_count' => $risks->where('risk_level', 'High')->count(),
        'moderate_risk_count' => $risks->where('risk_level', 'Moderate')->count(),
        'low_risk_count' => $risks->where('risk_level', 'Low')->count(),
        'average_risk_score' => $risks->avg('risk_score')
      ]
    ];
  }

  /**
   * Get team focus time analytics
   *
   * @param array $filters
   * @return array
   */
  public function getTeamFocusTimeAnalytics(array $filters): array
  {
    $teamEntries = WorkEntry::query()
      ->when($filters['start_date'] ?? null, fn($q) => $q->where('work_date', '>=', $filters['start_date']))
      ->when($filters['end_date'] ?? null, fn($q) => $q->where('work_date', '<=', $filters['end_date']))
      ->when($filters['department'] ?? null, fn($q) => $q->whereHas('user', fn($uq) => $uq->where('department', $filters['department']))
      )
      ->with('user')
      ->get()
      ->groupBy('user_id');

    $focusAnalytics = $teamEntries->map(function ($userEntries) {
      $user = $userEntries->first()->user;

      // Identify focus sessions (entries > 2 hours)
      $focusSessions = $userEntries->filter(fn($entry) => $entry->hours_worked > 2);

      // Calculate optimal focus times
      $optimalTimes = $this->calculateOptimalFocusTimes($focusSessions);

      // Analyze focus patterns
      $patterns = $this->analyzeFocusPatterns($focusSessions);

      // Calculate focus metrics
      $metrics = [
        'total_focus_hours' => $focusSessions->sum('hours_worked'),
        'average_session_length' => $focusSessions->avg('hours_worked') ?? 0,
        'focus_sessions_count' => $focusSessions->count(),
        'focus_time_percentage' => ($focusSessions->sum('hours_worked') / $userEntries->sum('hours_worked')) * 100
      ];

      return [
        'user_id' => $user->id,
        'name' => $user->name,
        'metrics' => array_map(fn($value) => round($value, 2), $metrics),
        'optimal_focus_times' => $optimalTimes,
        'patterns' => $patterns,
        'recommendations' => $this->generateFocusRecommendations($metrics, $patterns)
      ];
    });

    return [
      'team_focus_analytics' => $focusAnalytics->values()->toArray(),
      'summary' => [
        'average_focus_hours' => round($focusAnalytics->avg('metrics.total_focus_hours'), 2),
        'average_session_length' => round($focusAnalytics->avg('metrics.average_session_length'), 2),
        'team_focus_percentage' => round($focusAnalytics->avg('metrics.focus_time_percentage'), 2),
        'top_performers' => $focusAnalytics
          ->sortByDesc('metrics.focus_time_percentage')
          ->take(3)
          ->values()
          ->toArray()
      ]
    ];
  }

  /**
   * Helper method to calculate risk level
   *
   * @param int $score
   * @return string
   */
  private function calculateRiskLevel(int $score): string
  {
    return match (true) {
      $score >= 3 => 'High',
      $score >= 2 => 'Moderate',
      default => 'Low'
    };
  }

  /**
   * Helper method to calculate optimal focus times
   *
   * @param Collection $focusSessions
   * @return array
   */
  private function calculateOptimalFocusTimes(Collection $focusSessions): array
  {
    return $focusSessions
      ->groupBy(fn($entry) => $entry->created_at->format('H'))
      ->map(fn($sessions) => [
        'hour' => $sessions->first()->created_at->format('H'),
        'average_duration' => $sessions->avg('hours_worked'),
        'success_rate' => $sessions->where('status', 'completed')->count() / max($sessions->count(), 1)
      ])
      ->sortByDesc('success_rate')
      ->take(3)
      ->values()
      ->toArray();
  }

  /**
   * Helper method to analyze focus patterns
   *
   * @param Collection $focusSessions
   * @return array
   */
  private function analyzeFocusPatterns(Collection $focusSessions): array
  {
    $dayPatterns = $focusSessions
      ->groupBy(fn($entry) => $entry->work_date->format('l'))
      ->map(fn($sessions) => [
        'day' => $sessions->first()->work_date->format('l'),
        'average_sessions' => $sessions->count() / max($sessions->count(), 1),
        'total_hours' => $sessions->sum('hours_worked')
      ])
      ->sortByDesc('total_hours');

    return [
      'most_productive_days' => $dayPatterns->take(2)->values()->toArray(),
      'time_distribution' => $this->calculateTimeDistribution($focusSessions->toArray()),
      'session_success_rate' => $this->calculateSessionSuccessRate($focusSessions->toArray())
    ];
  }

  /**
   * Helper method to generate burnout recommendations
   *
   * @param array $riskFactors
   * @return array
   */
  private function generateBurnoutRecommendations(array $riskFactors): array
  {
    $recommendations = [];

    if ($riskFactors['high_hours']) {
      $recommendations[] = [
        'type' => 'warning',
        'message' => 'Consider reducing daily work hours to prevent burnout'
      ];
    }

    if ($riskFactors['inconsistent_pattern']) {
      $recommendations[] = [
        'type' => 'improvement',
        'message' => 'Try to maintain a more consistent work schedule'
      ];
    }

    if ($riskFactors['consecutive_long_days']) {
      $recommendations[] = [
        'type' => 'critical',
        'message' => 'Take breaks between long work stretches'
      ];
    }

    if ($riskFactors['weekend_work']) {
      $recommendations[] = [
        'type' => 'lifestyle',
        'message' => 'Reduce weekend work frequency to maintain work-life balance'
      ];
    }

    return $recommendations;
  }

  /**
   * Helper method to generate focus recommendations
   *
   * @param array $metrics
   * @param array $patterns
   * @return array
   */
  private function generateFocusRecommendations(array $metrics, array $patterns): array
  {
    $recommendations = [];

    if ($metrics['average_session_length'] < 2) {
      $recommendations[] = [
        'type' => 'improvement',
        'message' => 'Try to increase focus session duration to at least 2 hours'
      ];
    }

    if ($metrics['focus_time_percentage'] < 40) {
      $recommendations[] = [
        'type' => 'optimization',
        'message' => 'Aim to increase deep work percentage to 40% or more'
      ];
    }

    // Break frequency recommendations
    if (isset($metrics['break_frequency']) && $metrics['break_frequency'] > 120) {
      $recommendations[] = [
        'type' => 'health',
        'message' => 'Take more frequent breaks - aim for a 5-minute break every 90 minutes'
      ];
    }

    // Time of day optimization
    if (isset($patterns['peak_productivity_time'])) {
      $recommendations[] = [
        'type' => 'optimization',
        'message' => "Schedule your most important tasks during your peak productivity time ({$patterns['peak_productivity_time']})"
      ];
    }

    // Task completion rate
    if (isset($metrics['task_completion_rate']) && $metrics['task_completion_rate'] < 0.7) {
      $recommendations[] = [
        'type' => 'planning',
        'message' => 'Consider breaking down tasks into smaller, more manageable chunks'
      ];
    }

    // Context switching
    if (isset($metrics['context_switches']) && $metrics['context_switches'] > 10) {
      $recommendations[] = [
        'type' => 'focus',
        'message' => 'Reduce context switching by grouping similar tasks together'
      ];
    }

    // Work environment
    if (isset($patterns['interruption_frequency']) && $patterns['interruption_frequency'] > 5) {
      $recommendations[] = [
        'type' => 'environment',
        'message' => 'Find a quieter workspace or use "do not disturb" signals to reduce interruptions'
      ];
    }

    // Energy management
    if (isset($metrics['late_day_productivity']) && $metrics['late_day_productivity'] < 0.5) {
      $recommendations[] = [
        'type' => 'energy',
        'message' => 'Schedule challenging tasks earlier in the day when energy levels are higher'
      ];
    }

    // Session preparation
    if (isset($metrics['session_preparation_score']) && $metrics['session_preparation_score'] < 0.6) {
      $recommendations[] = [
        'type' => 'preparation',
        'message' => 'Prepare your workspace and materials before starting focus sessions'
      ];
    }

    // Weekly rhythm
    if (isset($patterns['weekly_consistency']) && $patterns['weekly_consistency'] < 0.7) {
      $recommendations[] = [
        'type' => 'consistency',
        'message' => 'Maintain a more consistent weekly schedule for better focus habits'
      ];
    }

    return $recommendations;
  }

  /**
   * Calculate the success rate of focus sessions.
   * Returns the percentage of successful sessions from the total sessions.
   *
   * @param array $focusSessions Array of focus session records
   * @return float Success rate as a percentage (0-100)
   */
  public function calculateSessionSuccessRate(array $focusSessions): float
  {
    if (empty($focusSessions)) {
      return 0.0;
    }

    $totalSessions = count($focusSessions);

    $successfulSessions = array_reduce($focusSessions, function ($carry, $session) {
      return $carry + (($session['success'] ?? false) ? 1 : 0);
    }, 0);

    return round(($successfulSessions / $totalSessions) * 100, 2);
  }

  /**
   * Calculate the time distribution across different activities in focus sessions.
   * Returns an array with activity types as keys and total duration in minutes as values.
   *
   * @param array $focusSessions Array of focus session records
   * @return array<string, int> Array of activity durations indexed by activity type
   */
  public function calculateTimeDistribution(array $focusSessions): array
  {
    $distribution = [];

    foreach ($focusSessions as $session) {
      if (!isset($session['activities']) || !is_array($session['activities'])) {
        continue;
      }

      foreach ($session['activities'] as $activity) {
        if (!isset($activity['type']) || !isset($activity['duration'])) {
          continue;
        }

        $activityType = $activity['type'];
        if (!isset($distribution[$activityType])) {
          $distribution[$activityType] = 0;
        }

        $distribution[$activityType] += (int)$activity['duration'];
      }
    }

    return $distribution;
  }
}
