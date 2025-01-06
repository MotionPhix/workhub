<?php

namespace App\Services;

use App\Models\WorkEntry;
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

  private function predictFutureProductivity($entries)
  {
    // Simple linear regression for productivity prediction
    $samples = $entries->pluck('hours_worked')->toArray();
    $targets = range(1, count($samples));

    $regression = new LeastSquares();
    $regression->train($samples, $targets);

    // Predict next 7 days
    $predictions = [];
    for ($i = 1; $i <= 7; $i++) {
      $predictions[] = $regression->predict($i);
    }

    return $predictions;
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
}
