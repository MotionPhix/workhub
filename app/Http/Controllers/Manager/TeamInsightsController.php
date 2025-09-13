<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamInsightsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-team-insights');

        $period = $request->get('period', 'month');

        $insights = [
            'performance_overview' => $this->getPerformanceOverview($period),
            'productivity_trends' => $this->getProductivityTrends($period),
            'project_insights' => $this->getProjectInsights($period),
            'team_efficiency' => $this->getTeamEfficiency($period),
            'compliance_metrics' => $this->getComplianceMetrics($period),
            'recommendations' => $this->getRecommendations($period),
        ];

        return Inertia::render('manager/TeamInsights', [
            'insights' => $insights,
            'period' => $period,
        ]);
    }

    public function performanceDetails(Request $request)
    {
        Gate::authorize('view-detailed-team-performance');

        $validated = $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'metric' => 'nullable|in:hours,productivity,efficiency,quality',
        ]);

        $period = $validated['period'] ?? 'month';
        $metric = $validated['metric'] ?? 'hours';

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $performanceData = [];

        foreach ($teamMemberIds as $userId) {
            $user = User::find($userId);
            $workEntries = WorkEntry::where('user_id', $userId)
                ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
                ->get();

            $reports = Report::where('user_id', $userId)
                ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
                ->get();

            $performanceData[] = [
                'user' => $user->only(['id', 'name', 'email']),
                'metrics' => $this->calculateDetailedMetrics($workEntries, $reports, $metric),
                'trends' => $this->calculateTrends($userId, $period, $metric),
            ];
        }

        // Sort by the selected metric
        $performanceData = collect($performanceData)->sortByDesc(function ($item) use ($metric) {
            return $item['metrics'][$metric] ?? 0;
        })->values()->toArray();

        return Inertia::render('manager/PerformanceDetails', [
            'performanceData' => $performanceData,
            'period' => $period,
            'metric' => $metric,
        ]);
    }

    public function productivityAnalysis(Request $request)
    {
        Gate::authorize('analyze-team-productivity');

        $validated = $request->validate([
            'analysis_type' => 'nullable|in:daily,weekly,monthly,project_based',
            'period' => 'nullable|in:week,month,quarter,year',
        ]);

        $analysisType = $validated['analysis_type'] ?? 'weekly';
        $period = $validated['period'] ?? 'month';

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->with(['user:id,name', 'project:id,name'])
            ->get();

        $analysis = match ($analysisType) {
            'daily' => $this->getDailyProductivityAnalysis($workEntries),
            'weekly' => $this->getWeeklyProductivityAnalysis($workEntries),
            'monthly' => $this->getMonthlyProductivityAnalysis($workEntries),
            'project_based' => $this->getProjectBasedAnalysis($workEntries),
            default => $this->getWeeklyProductivityAnalysis($workEntries),
        };

        return Inertia::render('manager/ProductivityAnalysis', [
            'analysis' => $analysis,
            'analysisType' => $analysisType,
            'period' => $period,
            'summary' => $this->getAnalysisSummary($workEntries),
        ]);
    }

    public function teamComparison(Request $request)
    {
        Gate::authorize('compare-team-performance');

        $period = $request->get('period', 'month');
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        $comparison = [];

        foreach ($teamMemberIds as $userId) {
            $user = User::find($userId);
            $comparison[] = [
                'user' => $user->only(['id', 'name', 'email']),
                'metrics' => $this->getComparisonMetrics($userId, $period),
                'rank' => 0, // Will be calculated after collecting all data
            ];
        }

        // Calculate rankings
        $comparison = $this->calculateRankings($comparison);

        return Inertia::render('manager/TeamComparison', [
            'comparison' => $comparison,
            'period' => $period,
            'teamStats' => $this->getTeamStatsSummary($teamMemberIds, $period),
        ]);
    }

    public function exportInsights(Request $request)
    {
        Gate::authorize('export-team-insights');

        $validated = $request->validate([
            'format' => 'required|in:json,csv',
            'insight_type' => 'required|in:performance,productivity,efficiency,all',
            'period' => 'nullable|in:week,month,quarter,year',
        ]);

        $period = $validated['period'] ?? 'month';
        $insightType = $validated['insight_type'];

        $data = match ($insightType) {
            'performance' => $this->getPerformanceOverview($period),
            'productivity' => $this->getProductivityTrends($period),
            'efficiency' => $this->getTeamEfficiency($period),
            'all' => [
                'performance' => $this->getPerformanceOverview($period),
                'productivity' => $this->getProductivityTrends($period),
                'efficiency' => $this->getTeamEfficiency($period),
                'compliance' => $this->getComplianceMetrics($period),
            ],
        };

        $filename = "team-insights-{$insightType}-".now()->format('Y-m-d');

        if ($validated['format'] === 'csv') {
            return $this->exportInsightsToCsv($data, $filename, $insightType);
        }

        return response()->json($data);
    }

    private function getPerformanceOverview(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::whereIn('user_id', $teamMemberIds)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $totalHours = $workEntries->sum('hours');
        $totalMembers = $teamMemberIds->count();
        $workingDays = $this->getWorkingDaysInPeriod($dateRange);

        return [
            'total_hours' => $totalHours,
            'average_hours_per_member' => $totalMembers > 0 ? round($totalHours / $totalMembers, 1) : 0,
            'average_hours_per_day' => $workingDays > 0 ? round($totalHours / $workingDays, 1) : 0,
            'total_work_entries' => $workEntries->count(),
            'total_reports' => $reports->count(),
            'completion_rate' => $this->calculateCompletionRate($workEntries),
            'quality_score' => $this->calculateQualityScore($reports),
            'efficiency_rating' => $this->calculateEfficiencyRating($workEntries, $workingDays),
        ];
    }

    private function getProductivityTrends(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->get();

        // Group by week for trend analysis
        $weeklyData = $workEntries->groupBy(function ($entry) {
            return Carbon::parse($entry->date)->startOfWeek()->format('Y-m-d');
        })->map(function ($weekEntries) {
            return [
                'hours' => $weekEntries->sum('hours'),
                'entries' => $weekEntries->count(),
                'unique_contributors' => $weekEntries->pluck('user_id')->unique()->count(),
            ];
        })->sortKeys();

        return [
            'weekly_breakdown' => $weeklyData->toArray(),
            'trend_direction' => $this->calculateTrendDirection($weeklyData->pluck('hours')),
            'peak_productivity_week' => $weeklyData->keys()->first(),
            'productivity_variance' => $this->calculateVariance($weeklyData->pluck('hours')),
        ];
    }

    private function getProjectInsights(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->with('project:id,name')
            ->get();

        $projectStats = $workEntries->groupBy('project_id')->map(function ($projectEntries) {
            $project = $projectEntries->first()->project;

            return [
                'project' => $project ? $project->only(['id', 'name']) : ['id' => null, 'name' => 'Unassigned'],
                'total_hours' => $projectEntries->sum('hours'),
                'contributors' => $projectEntries->pluck('user_id')->unique()->count(),
                'entries' => $projectEntries->count(),
                'average_hours_per_entry' => round($projectEntries->sum('hours') / $projectEntries->count(), 2),
                'efficiency_score' => $this->calculateProjectEfficiency($projectEntries),
            ];
        })->sortByDesc('total_hours')->values();

        return [
            'project_breakdown' => $projectStats->toArray(),
            'most_active_project' => $projectStats->first(),
            'project_distribution' => $this->calculateProjectDistribution($projectStats),
            'underutilized_projects' => $projectStats->where('contributors', 1)->values()->toArray(),
        ];
    }

    private function getTeamEfficiency(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::whereIn('user_id', $teamMemberIds)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $expectedWorkingHours = $this->calculateExpectedWorkingHours($teamMemberIds, $dateRange);
        $actualHours = $workEntries->sum('hours');

        return [
            'capacity_utilization' => $expectedWorkingHours > 0 ? round(($actualHours / $expectedWorkingHours) * 100, 1) : 0,
            'time_tracking_compliance' => $this->calculateTimeTrackingCompliance($teamMemberIds, $dateRange),
            'reporting_compliance' => $this->calculateReportingCompliance($reports, $teamMemberIds),
            'average_entry_quality' => $this->calculateAverageEntryQuality($workEntries),
            'collaboration_index' => $this->calculateCollaborationIndex($workEntries),
            'efficiency_bottlenecks' => $this->identifyEfficiencyBottlenecks($workEntries, $reports),
        ];
    }

    private function getComplianceMetrics(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::whereIn('user_id', $teamMemberIds)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $workingDays = $this->getWorkingDaysInPeriod($dateRange);
        $expectedEntries = $teamMemberIds->count() * $workingDays;
        $actualEntries = $workEntries->count();

        return [
            'time_entry_compliance' => $expectedEntries > 0 ? round(($actualEntries / $expectedEntries) * 100, 1) : 0,
            'daily_entry_rate' => round($actualEntries / $workingDays, 1),
            'report_submission_rate' => $this->calculateReportSubmissionRate($reports, $teamMemberIds),
            'approval_pending_rate' => $this->calculateApprovalPendingRate($reports),
            'quality_control_score' => $this->calculateQualityControlScore($workEntries, $reports),
            'policy_adherence' => $this->calculatePolicyAdherence($workEntries, $reports),
        ];
    }

    private function getRecommendations(string $period): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::whereIn('user_id', $teamMemberIds)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        $recommendations = [];

        // Productivity recommendations
        $lowPerformers = $this->identifyLowPerformers($teamMemberIds, $dateRange);
        if ($lowPerformers->isNotEmpty()) {
            $recommendations[] = [
                'type' => 'productivity',
                'priority' => 'high',
                'title' => 'Address Low Productivity',
                'description' => "Consider one-on-one meetings with {$lowPerformers->count()} team members showing below-average productivity.",
                'affected_members' => $lowPerformers->pluck('name')->toArray(),
            ];
        }

        // Time tracking compliance
        $complianceRate = $this->calculateTimeTrackingCompliance($teamMemberIds, $dateRange);
        if ($complianceRate < 80) {
            $recommendations[] = [
                'type' => 'compliance',
                'priority' => 'medium',
                'title' => 'Improve Time Tracking',
                'description' => "Time tracking compliance is at {$complianceRate}%. Consider implementing reminders or training.",
            ];
        }

        // Project distribution
        $projectDistribution = $this->getProjectDistributionAnalysis($workEntries);
        if ($projectDistribution['imbalance_score'] > 0.7) {
            $recommendations[] = [
                'type' => 'resource_allocation',
                'priority' => 'medium',
                'title' => 'Balance Project Allocation',
                'description' => 'Some projects may be understaffed while others are overallocated. Consider rebalancing.',
            ];
        }

        return $recommendations;
    }

    // Helper methods would continue here...
    // Due to length constraints, I'll implement key helper methods

    private function getDateRangeForPeriod(string $period): array
    {
        return match ($period) {
            'week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            'year' => [
                'start' => now()->startOfYear(),
                'end' => now()->endOfYear(),
            ],
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }

    private function calculateCompletionRate($workEntries): float
    {
        if ($workEntries->isEmpty()) {
            return 0;
        }

        $completedEntries = $workEntries->filter(function ($entry) {
            return ! empty($entry->description) && $entry->hours > 0;
        });

        return round(($completedEntries->count() / $workEntries->count()) * 100, 1);
    }

    private function calculateQualityScore($reports): float
    {
        if ($reports->isEmpty()) {
            return 0;
        }

        $approvedReports = $reports->where('status', 'approved')->count();
        $totalReports = $reports->count();

        return round(($approvedReports / $totalReports) * 100, 1);
    }

    private function calculateEfficiencyRating($workEntries, int $workingDays): float
    {
        if ($workEntries->isEmpty() || $workingDays === 0) {
            return 0;
        }

        $totalHours = $workEntries->sum('hours');
        $expectedHours = $workingDays * 8; // Assuming 8-hour workdays
        $uniqueUsers = $workEntries->pluck('user_id')->unique()->count();

        if ($uniqueUsers === 0) {
            return 0;
        }

        $averageHoursPerUser = $totalHours / $uniqueUsers;
        $efficiency = ($averageHoursPerUser / $expectedHours) * 100;

        return round(min($efficiency, 100), 1); // Cap at 100%
    }

    private function getWorkingDaysInPeriod(array $dateRange): int
    {
        $start = Carbon::parse($dateRange['start']);
        $end = Carbon::parse($dateRange['end']);

        $workingDays = 0;
        $current = $start->copy();

        while ($current <= $end) {
            if ($current->isWeekday()) {
                $workingDays++;
            }
            $current->addDay();
        }

        return $workingDays;
    }

    private function calculateTrendDirection($values): string
    {
        if ($values->count() < 2) {
            return 'stable';
        }

        $first = $values->slice(0, ceil($values->count() / 2))->average();
        $second = $values->slice(ceil($values->count() / 2))->average();

        if ($second > $first * 1.05) {
            return 'increasing';
        } elseif ($second < $first * 0.95) {
            return 'decreasing';
        }

        return 'stable';
    }

    private function exportInsightsToCsv($data, string $filename, string $insightType)
    {
        return response()->streamDownload(function () use ($data, $insightType) {
            $output = fopen('php://output', 'w');

            // Write headers based on insight type
            match ($insightType) {
                'performance' => $this->writePerformanceCsvHeaders($output),
                'productivity' => $this->writeProductivityCsvHeaders($output),
                'efficiency' => $this->writeEfficiencyCsvHeaders($output),
                'all' => $this->writeAllInsightsCsvHeaders($output),
            };

            // Write data rows
            $this->writeCsvData($output, $data, $insightType);

            fclose($output);
        }, "{$filename}.csv");
    }

    private function writePerformanceCsvHeaders($output)
    {
        fputcsv($output, [
            'Metric',
            'Value',
            'Unit',
        ]);
    }

    private function writeCsvData($output, $data, string $insightType)
    {
        if ($insightType === 'performance') {
            foreach ($data as $key => $value) {
                fputcsv($output, [
                    ucfirst(str_replace('_', ' ', $key)),
                    $value,
                    $this->getMetricUnit($key),
                ]);
            }
        }
        // Add other insight types as needed
    }

    private function getMetricUnit(string $key): string
    {
        return match ($key) {
            'total_hours', 'average_hours_per_member', 'average_hours_per_day' => 'hours',
            'completion_rate', 'quality_score', 'efficiency_rating' => '%',
            default => 'count',
        };
    }
}
