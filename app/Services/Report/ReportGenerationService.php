<?php

namespace App\Services\Report;

use App\Models\WorkEntry;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportGenerationService
{
    public function generateReportData(array $filters): array
    {
        return [
            'summary' => $this->generateSummary($filters),
            'details' => $this->generateDetails($filters),
            'insights' => $this->generateInsights($filters),
        ];
    }

    public function getDepartmentStats(): Collection
    {
        return DB::table('departments')
            ->select('name', DB::raw('count(*) as count'))
            ->groupBy('name')
            ->get();
    }

    public function getProductivityTrends(array $timeRange): Collection
    {
        return WorkEntry::select(
            DB::raw('DATE(work_date) as date'),
            DB::raw('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time)) as score')
        )
            ->whereBetween('work_date', $timeRange)
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($trend) {
                return [
                    'date' => $trend->date,
                    'score' => round($trend->score * 10, 1), // Convert to 0-100 scale
                ];
            });
    }

    public function getTopPerformers(array $filters): Collection
    {
        return WorkEntry::with('user')
            ->select('user_id', DB::raw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time)) as total_hours'))
            ->when($filters['department'] ?? null, function ($query, $department) {
                $query->whereHas('user', fn ($q) => $q->where('department', $department));
            })
            ->whereBetween('work_date', [
                $filters['start_date'] ?? Carbon::now()->subDays(30),
                $filters['end_date'] ?? Carbon::now(),
            ])
            ->groupBy('user_id')
            ->orderByDesc('total_hours')
            ->limit(5)
            ->get();
    }

    protected function generateSummary(array $filters): array
    {
        $query = WorkEntry::query()
            ->when($filters['start_date'] ?? null, fn ($q) => $q->where('work_date', '>=', $filters['start_date']))
            ->when($filters['end_date'] ?? null, fn ($q) => $q->where('work_date', '<=', $filters['end_date']));

        return [
            'total_entries' => $query->count(),
            'total_hours' => $query->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
                ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0,
            'average_hours' => $query->selectRaw('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
                ->value('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0,
            'completion_rate' => $this->calculateCompletionRate($query),
        ];
    }

    protected function generateDetails(array $filters): Collection
    {
        return WorkEntry::with(['user', 'tags'])
            ->when($filters['department'] ?? null, function ($query, $department) {
                $query->whereHas('user', fn ($q) => $q->where('department', $department));
            })
            ->when($filters['status'] ?? null, fn ($q) => $q->where('status', $filters['status']))
            ->when($filters['tag'] ?? null, function ($query, $tag) {
                $query->whereHas('tags', fn ($q) => $q->where('name', $tag));
            })
            ->orderBy('work_date', 'desc')
            ->get();
    }

    protected function generateInsights(array $filters): array
    {
        return [
            'productivity_trends' => $this->getProductivityTrends([
                $filters['start_date'] ?? Carbon::now()->subDays(30),
                $filters['end_date'] ?? Carbon::now(),
            ]),
            'department_performance' => $this->getDepartmentPerformance($filters),
            'top_performers' => $this->getTopPerformers($filters),
        ];
    }

    protected function calculateCompletionRate($query): float
    {
        $total = $query->count();
        if ($total === 0) {
            return 0;
        }

        $completed = $query->where('status', 'completed')->count();

        return round(($completed / $total) * 100, 2);
    }

    protected function getDepartmentPerformance(array $filters): Collection
    {
        return DB::table('work_entries')
            ->join('users', 'work_entries.user_id', '=', 'users.id')
            ->select(
                'users.department',
                DB::raw('COUNT(*) as total_entries'),
                DB::raw('SUM(TIMESTAMPDIFF(HOUR, work_entries.start_date_time, work_entries.end_date_time)) as total_hours'),
                DB::raw('AVG(TIMESTAMPDIFF(HOUR, work_entries.start_date_time, work_entries.end_date_time)) as average_hours')
            )
            ->when($filters['start_date'] ?? null, fn ($q) => $q->where('work_date', '>=', $filters['start_date']))
            ->when($filters['end_date'] ?? null, fn ($q) => $q->where('work_date', '<=', $filters['end_date']))
            ->groupBy('users.department')
            ->get();
    }

    /**
     * Generate productivity trends for a specific work entry
     */
    public function generateProductivityTrends(WorkEntry $report): array
    {
        $userId = $report->user_id;
        $date = Carbon::parse($report->work_date);

        // Get entries for the last 30 days from the report date
        $entries = WorkEntry::where('user_id', $userId)
            ->where('work_date', '<=', $date)
            ->where('work_date', '>=', $date->copy()->subDays(30))
            ->orderBy('work_date')
            ->get();

        return [
            'daily' => $this->getDailyTrends($entries),
            'weekly' => $this->getWeeklyTrends($entries),
            'efficiency' => $this->calculateEfficiencyTrends($entries),
            'comparison' => $this->getTeamComparison($report),
            'patterns' => $this->analyzeWorkPatterns($entries),
        ];
    }

    /**
     * Calculate daily productivity trends
     */
    protected function getDailyTrends(Collection $entries): array
    {
        return $entries
            ->groupBy(fn ($entry) => $entry->work_date->format('Y-m-d'))
            ->map(fn ($group) => [
                'date' => $group->first()->work_date->format('Y-m-d'),
                'hours' => $group->sum('hours_worked'),
                'entries' => $group->count(),
                'efficiency_score' => $this->calculateEfficiencyScore($group),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Calculate weekly aggregated trends
     */
    protected function getWeeklyTrends(Collection $entries): array
    {
        return $entries
            ->groupBy(fn ($entry) => $entry->work_date->format('W'))
            ->map(fn ($group) => [
                'week' => $group->first()->work_date->format('W'),
                'start_date' => $group->first()->work_date->startOfWeek()->format('Y-m-d'),
                'end_date' => $group->first()->work_date->endOfWeek()->format('Y-m-d'),
                'average_hours' => $group->average('hours_worked'),
                'total_hours' => $group->sum('hours_worked'),
                'productivity_score' => $this->calculateProductivityScore($group),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Calculate efficiency trends based on work patterns
     */
    protected function calculateEfficiencyTrends(Collection $entries): array
    {
        $baselineHours = 8; // Standard work hours
        $baselineEfficiency = 1.0; // Baseline efficiency score

        return $entries
            ->map(fn ($entry) => [
                'date' => $entry->work_date->format('Y-m-d'),
                'efficiency' => min(
                    ($entry->hours_worked / $baselineHours) * $baselineEfficiency,
                    1.5
                ),
                'factors' => $this->analyzeEfficiencyFactors($entry),
            ])
            ->values()
            ->toArray();
    }

    /**
     * Get team comparison metrics
     */
    protected function getTeamComparison(WorkEntry $report): array
    {
        $teamAverage = WorkEntry::where('work_date', $report->work_date)
            ->whereHas('user', fn ($query) => $query->where('department', $report->user->department)
            )
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
            ->value('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;

        return [
            'user_hours' => $report->hours_worked,
            'team_average' => round($teamAverage, 2),
            'difference' => round($report->hours_worked - $teamAverage, 2),
            'percentile' => $this->calculateTeamPercentile($report),
        ];
    }

    /**
     * Analyze work patterns for insights
     */
    protected function analyzeWorkPatterns(Collection $entries): array
    {
        $patterns = [
            'most_productive_days' => $this->findMostProductiveDays($entries),
            'optimal_hours' => $this->calculateOptimalHours($entries),
            'consistency_score' => $this->calculateConsistencyScore($entries),
        ];

        return array_merge($patterns, [
            'recommendations' => $this->generateRecommendations($patterns),
        ]);
    }

    /**
     * Calculate efficiency score for a group of entries
     */
    protected function calculateEfficiencyScore(Collection $entries): float
    {
        $totalHours = $entries->sum('hours_worked');
        $entryCount = $entries->count();

        if ($entryCount === 0) {
            return 0;
        }

        $averageHours = $totalHours / $entryCount;
        $consistencyFactor = 1 - ($entries->std('hours_worked') / $averageHours);

        return round(min(($averageHours / 8) * $consistencyFactor * 100, 100), 2);
    }

    /**
     * Calculate team percentile for a work entry
     */
    protected function calculateTeamPercentile(WorkEntry $report): int
    {
        $teamHours = WorkEntry::where('work_date', $report->work_date)
            ->whereHas('user', fn ($query) => $query->where('department', $report->user->department)
            )
            ->pluck('hours_worked')
            ->sort()
            ->values();

        $position = $teamHours->search($report->hours_worked);

        if ($position === false) {
            return 0;
        }

        return round(($position / max($teamHours->count() - 1, 1)) * 100);
    }

    /**
     * Calculate productivity score based on multiple factors
     */
    protected function calculateProductivityScore(Collection $entries): float
    {
        if ($entries->isEmpty()) {
            return 0.0;
        }

        // Calculate base metrics
        $totalHours = $entries->sum('hours_worked');
        $averageHours = $entries->avg('hours_worked');
        $entryCount = $entries->count();
        $uniqueDays = $entries->pluck('work_date')->unique()->count();

        // Calculate consistency factor
        $standardDeviation = $entries->std('hours_worked') ?: 1;
        $consistencyFactor = 1 - (min($standardDeviation / $averageHours, 1));

        // Calculate completion rate
        $completedTasks = $entries->where('status', 'completed')->count();
        $completionRate = $entryCount > 0 ? $completedTasks / $entryCount : 0;

        // Calculate time utilization
        $optimalHoursPerDay = 8.0;
        $timeUtilization = min($averageHours / $optimalHoursPerDay, 1.2);

        // Weighted scoring
        $weights = [
            'consistency' => 0.3,
            'completion' => 0.3,
            'utilization' => 0.4,
        ];

        $score = (
            ($consistencyFactor * $weights['consistency']) +
            ($completionRate * $weights['completion']) +
            ($timeUtilization * $weights['utilization'])
        ) * 100;

        return round(min($score, 100), 2);
    }

    /**
     * Analyze efficiency factors for a work entry
     */
    protected function analyzeEfficiencyFactors(WorkEntry $entry): array
    {
        $optimalHours = 8.0;
        $maxEfficiency = 1.5;

        // Calculate base efficiency
        $baseEfficiency = min($entry->hours_worked / $optimalHours, $maxEfficiency);

        // Analyze time of day effect
        $timeOfDay = Carbon::parse($entry->created_at)->hour;
        $timeEffect = $this->calculateTimeOfDayEffect($timeOfDay);

        // Calculate workload impact
        $dailyEntries = WorkEntry::where('user_id', $entry->user_id)
            ->where('work_date', $entry->work_date)
            ->count();
        $workloadImpact = $this->calculateWorkloadImpact($dailyEntries);

        return [
            'base_efficiency' => round($baseEfficiency, 2),
            'time_effect' => round($timeEffect, 2),
            'workload_impact' => round($workloadImpact, 2),
            'final_score' => round(
                ($baseEfficiency * $timeEffect * $workloadImpact),
                2
            ),
        ];
    }

    /**
     * Find the most productive days based on historical data
     */
    protected function findMostProductiveDays(Collection $entries): array
    {
        $dayStats = $entries
            ->groupBy(fn ($entry) => $entry->work_date->format('l'))
            ->map(function ($dayEntries) {
                $totalHours = $dayEntries->sum('hours_worked');
                $avgHours = $dayEntries->avg('hours_worked');
                $completionRate = $dayEntries->where('status', 'completed')->count() / max($dayEntries->count(), 1);

                return [
                    'total_hours' => round($totalHours, 2),
                    'average_hours' => round($avgHours, 2),
                    'completion_rate' => round($completionRate * 100, 2),
                    'productivity_score' => $this->calculateProductivityScore($dayEntries),
                ];
            })
            ->sortByDesc('productivity_score');

        return [
            'most_productive' => $dayStats->take(3)->toArray(),
            'least_productive' => $dayStats->take(-2)->toArray(),
        ];
    }

    /**
     * Calculate optimal working hours based on performance data
     */
    protected function calculateOptimalHours(Collection $entries): array
    {
        $hourlyPerformance = [];

        // Analyze performance by hour ranges
        for ($hour = 1; $hour <= 12; $hour++) {
            $entriesInRange = $entries->filter(function ($entry) use ($hour) {
                return $entry->hours_worked >= $hour && $entry->hours_worked < ($hour + 1);
            });

            if ($entriesInRange->isNotEmpty()) {
                $hourlyPerformance[$hour] = [
                    'count' => $entriesInRange->count(),
                    'completion_rate' => $entriesInRange->where('status', 'completed')->count() / $entriesInRange->count(),
                    'average_score' => $this->calculateProductivityScore($entriesInRange),
                ];
            }
        }

        // Find optimal range
        $optimal = collect($hourlyPerformance)
            ->sortByDesc('average_score')
            ->first();

        return [
            'optimal_range' => [
                'start' => $optimal ? key($hourlyPerformance) : 8,
                'end' => $optimal ? key($hourlyPerformance) + 1 : 9,
            ],
            'performance_data' => $hourlyPerformance,
        ];
    }

    /**
     * Calculate consistency score based on work patterns
     */
    protected function calculateConsistencyScore(Collection $entries): array
    {
        $dailyStats = $entries->groupBy(fn ($entry) => $entry->work_date->format('Y-m-d'));

        // Calculate daily variations
        $hoursVariation = $dailyStats
            ->map(fn ($day) => $day->sum('hours_worked'))
            ->std() ?: 0;

        // Calculate time pattern consistency
        $timePatternScore = $this->analyzeTimePatternConsistency($entries);

        // Calculate work distribution score
        $distributionScore = $this->analyzeWorkDistribution($dailyStats);

        $finalScore = round(
            (
                (1 - min($hoursVariation / 8, 1)) * 0.4 +
                $timePatternScore * 0.3 +
                $distributionScore * 0.3
            ) * 100,
            2
        );

        return [
            'score' => $finalScore,
            'factors' => [
                'hours_variation' => round($hoursVariation, 2),
                'time_pattern' => round($timePatternScore * 100, 2),
                'distribution' => round($distributionScore * 100, 2),
            ],
            'level' => $this->getConsistencyLevel($finalScore),
        ];
    }

    /**
     * Generate recommendations based on work patterns
     */
    protected function generateRecommendations(array $patterns): array
    {
        $recommendations = [];

        // Analyze optimal hours
        if (isset($patterns['optimal_hours'])) {
            $optimalRange = $patterns['optimal_hours']['optimal_range'];
            $recommendations[] = [
                'type' => 'work_hours',
                'priority' => 'high',
                'suggestion' => "Consider working between {$optimalRange['start']} and {$optimalRange['end']} hours per day for optimal productivity",
            ];
        }

        // Analyze consistency
        if (isset($patterns['consistency_score'])) {
            $consistencyScore = $patterns['consistency_score']['score'];
            if ($consistencyScore < 70) {
                $recommendations[] = [
                    'type' => 'consistency',
                    'priority' => 'medium',
                    'suggestion' => 'Try to maintain more consistent working hours throughout the week',
                ];
            }
        }

        // Analyze productive days
        if (isset($patterns['most_productive_days']['most_productive'])) {
            $bestDay = array_key_first($patterns['most_productive_days']['most_productive']);
            $recommendations[] = [
                'type' => 'scheduling',
                'priority' => 'medium',
                'suggestion' => "Schedule important tasks on {$bestDay}s when your productivity is highest",
            ];
        }

        return [
            'recommendations' => $recommendations,
            'priority_actions' => array_filter($recommendations, fn ($r) => $r['priority'] === 'high'),
            'generated_at' => now()->toDateTimeString(),
        ];
    }

    /**
     * Helper method to calculate time of day effect on productivity
     */
    private function calculateTimeOfDayEffect(int $hour): float
    {
        $peakHours = [9, 10, 11, 14, 15, 16];
        $goodHours = [8, 12, 13, 17];

        if (in_array($hour, $peakHours)) {
            return 1.2;
        }
        if (in_array($hour, $goodHours)) {
            return 1.0;
        }

        return 0.8;
    }

    /**
     * Helper method to calculate workload impact
     */
    private function calculateWorkloadImpact(int $dailyEntries): float
    {
        $optimalEntries = 5;
        $maxEntries = 10;

        if ($dailyEntries <= $optimalEntries) {
            return 1.0;
        }
        if ($dailyEntries >= $maxEntries) {
            return 0.7;
        }

        return 1.0 - (($dailyEntries - $optimalEntries) * 0.06);
    }

    /**
     * Helper method to analyze time pattern consistency
     */
    private function analyzeTimePatternConsistency(Collection $entries): float
    {
        $startTimes = $entries->groupBy(fn ($entry) => Carbon::parse($entry->created_at)->format('H')
        );

        $mostCommonHour = $startTimes->sortByDesc->count()->keys()->first();
        $entriesInCommonHour = $startTimes[$mostCommonHour]->count();

        return $entriesInCommonHour / max($entries->count(), 1);
    }

    /**
     * Helper method to analyze work distribution
     */
    private function analyzeWorkDistribution(Collection $dailyStats): float
    {
        $totalDays = $dailyStats->count();
        if ($totalDays === 0) {
            return 0;
        }

        $daysWithOptimalHours = $dailyStats
            ->filter(fn ($day) => $day->sum('hours_worked') >= 6 &&
              $day->sum('hours_worked') <= 9
            )
            ->count();

        return $daysWithOptimalHours / $totalDays;
    }

    /**
     * Helper method to get consistency level
     */
    private function getConsistencyLevel(float $score): string
    {
        return match (true) {
            $score >= 90 => 'Excellent',
            $score >= 75 => 'Good',
            $score >= 60 => 'Fair',
            default => 'Needs Improvement'
        };
    }
}
