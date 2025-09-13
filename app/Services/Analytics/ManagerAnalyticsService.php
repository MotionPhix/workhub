<?php

namespace App\Services\Analytics;

use App\Models\Report;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ManagerAnalyticsService
{
    public function getManagerDashboardData(User $manager): array
    {
        $teamMembers = $this->getTeamMembers($manager);
        $timeframe = $this->getDefaultTimeframe();

        return [
            'team_overview' => $this->getTeamOverview($teamMembers),
            'report_metrics' => $this->getReportMetrics($teamMembers, $timeframe),
            'performance_analytics' => $this->getPerformanceAnalytics($teamMembers, $timeframe),
            'compliance_status' => $this->getComplianceStatus($teamMembers),
            'trending_insights' => $this->getTrendingInsights($teamMembers, $timeframe),
            'upcoming_reports' => $this->getUpcomingReports($teamMembers),
            'team_rankings' => $this->getTeamRankings($teamMembers, $timeframe),
        ];
    }

    private function getTeamMembers(User $manager): Collection
    {
        return User::where('manager_email', $manager->email)
            ->with(['department', 'roles'])
            ->active()
            ->get();
    }

    private function getDefaultTimeframe(): array
    {
        return [
            'current_month' => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
            'last_month' => [
                'start' => now()->subMonth()->startOfMonth(),
                'end' => now()->subMonth()->endOfMonth(),
            ],
            'current_quarter' => [
                'start' => now()->startOfQuarter(),
                'end' => now()->endOfQuarter(),
            ],
            'last_quarter' => [
                'start' => now()->subQuarter()->startOfQuarter(),
                'end' => now()->subQuarter()->endOfQuarter(),
            ],
        ];
    }

    private function getTeamOverview(Collection $teamMembers): array
    {
        $totalMembers = $teamMembers->count();
        $activeMembers = $teamMembers->filter(function ($user) {
            return $user->last_login_at && $user->last_login_at->gte(now()->subDays(7));
        })->count();

        $departmentDistribution = $teamMembers->groupBy('department.name')
            ->map->count()
            ->toArray();

        $roleDistribution = $teamMembers->flatMap(function ($user) {
            return $user->roles->pluck('name');
        })->countBy()->toArray();

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'inactive_members' => $totalMembers - $activeMembers,
            'activity_rate' => $totalMembers > 0 ? round(($activeMembers / $totalMembers) * 100, 1) : 0,
            'department_distribution' => $departmentDistribution,
            'role_distribution' => $roleDistribution,
            'newest_member' => $teamMembers->sortByDesc('created_at')->first()?->only(['id', 'name', 'created_at']),
        ];
    }

    private function getReportMetrics(Collection $teamMembers, array $timeframe): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();

        $currentMonthReports = Report::whereIn('user_id', $teamIds)
            ->whereBetween('created_at', [
                $timeframe['current_month']['start'],
                $timeframe['current_month']['end'],
            ])
            ->get();

        $lastMonthReports = Report::whereIn('user_id', $teamIds)
            ->whereBetween('created_at', [
                $timeframe['last_month']['start'],
                $timeframe['last_month']['end'],
            ])
            ->get();

        $reportsByType = $currentMonthReports->groupBy('report_type')
            ->map->count()
            ->toArray();

        $reportsByStatus = $currentMonthReports->groupBy('status')
            ->map->count()
            ->toArray();

        $onTimeReports = $currentMonthReports->filter(function ($report) {
            return $report->submitted_at &&
                   $report->submitted_at->lte($report->created_at->addDays(7)); // Within 7 days
        })->count();

        return [
            'current_month' => [
                'total_reports' => $currentMonthReports->count(),
                'approved_reports' => $reportsByStatus['approved'] ?? 0,
                'pending_reports' => $reportsByStatus['pending'] ?? 0,
                'draft_reports' => $reportsByStatus['draft'] ?? 0,
                'rejected_reports' => $reportsByStatus['rejected'] ?? 0,
                'on_time_reports' => $onTimeReports,
                'on_time_percentage' => $currentMonthReports->count() > 0
                    ? round(($onTimeReports / $currentMonthReports->count()) * 100, 1)
                    : 0,
            ],
            'comparison' => [
                'reports_change' => $currentMonthReports->count() - $lastMonthReports->count(),
                'reports_change_percent' => $lastMonthReports->count() > 0
                    ? round((($currentMonthReports->count() - $lastMonthReports->count()) / $lastMonthReports->count()) * 100, 1)
                    : 0,
            ],
            'by_type' => $reportsByType,
            'by_status' => $reportsByStatus,
        ];
    }

    private function getPerformanceAnalytics(Collection $teamMembers, array $timeframe): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();

        // Get individual performance metrics
        $individualPerformance = $teamMembers->map(function ($member) use ($timeframe) {
            $memberReports = Report::where('user_id', $member->id)
                ->whereBetween('created_at', [
                    $timeframe['current_month']['start'],
                    $timeframe['current_month']['end'],
                ])
                ->get();

            $avgCompletionTime = $memberReports->filter(function ($report) {
                return $report->submitted_at && $report->created_at;
            })->avg(function ($report) {
                return $report->created_at->diffInDays($report->submitted_at);
            });

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'department' => $member->department?->name,
                'reports_count' => $memberReports->count(),
                'approved_count' => $memberReports->where('status', 'approved')->count(),
                'on_time_count' => $memberReports->filter(function ($report) {
                    return $report->submitted_at &&
                           $report->submitted_at->lte($report->created_at->addDays(7));
                })->count(),
                'avg_completion_time' => $avgCompletionTime,
                'approval_rate' => $memberReports->count() > 0
                    ? round(($memberReports->where('status', 'approved')->count() / $memberReports->count()) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('reports_count');

        // Calculate team averages
        $teamAverages = [
            'avg_reports_per_member' => $individualPerformance->avg('reports_count'),
            'avg_approval_rate' => $individualPerformance->avg('approval_rate'),
            'avg_completion_time' => $individualPerformance->avg('avg_completion_time'),
        ];

        return [
            'individual_performance' => $individualPerformance->values()->toArray(),
            'team_averages' => $teamAverages,
            'top_performers' => $individualPerformance->take(3)->values()->toArray(),
            'needs_attention' => $individualPerformance->filter(function ($member) {
                return $member['approval_rate'] < 70 || $member['reports_count'] == 0;
            })->take(3)->values()->toArray(),
        ];
    }

    private function getComplianceStatus(Collection $teamMembers): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();
        $oneWeekAgo = now()->subWeek();

        $membersWithRecentReports = Report::whereIn('user_id', $teamIds)
            ->where('created_at', '>=', $oneWeekAgo)
            ->distinct('user_id')
            ->count('user_id');

        $overdue = $teamMembers->filter(function ($member) {
            $lastReport = Report::where('user_id', $member->id)
                ->latest('created_at')
                ->first();

            return ! $lastReport || $lastReport->created_at->lt(now()->subDays(14));
        });

        $pendingApproval = Report::whereIn('user_id', $teamIds)
            ->where('status', 'pending')
            ->count();

        return [
            'compliant_members' => $membersWithRecentReports,
            'non_compliant_members' => $teamMembers->count() - $membersWithRecentReports,
            'compliance_rate' => $teamMembers->count() > 0
                ? round(($membersWithRecentReports / $teamMembers->count()) * 100, 1)
                : 0,
            'overdue_reports' => $overdue->count(),
            'pending_approvals' => $pendingApproval,
            'overdue_members' => $overdue->map(function ($member) {
                $lastReport = Report::where('user_id', $member->id)
                    ->latest('created_at')
                    ->first();

                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'department' => $member->department?->name,
                    'days_overdue' => $lastReport
                        ? $lastReport->created_at->diffInDays(now())
                        : 'No reports',
                ];
            })->values()->toArray(),
        ];
    }

    private function getTrendingInsights(Collection $teamMembers, array $timeframe): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();

        // Get quarterly trends
        $quarterlyData = [];
        for ($i = 0; $i < 4; $i++) {
            $quarterStart = now()->subQuarters($i)->startOfQuarter();
            $quarterEnd = now()->subQuarters($i)->endOfQuarter();

            $reports = Report::whereIn('user_id', $teamIds)
                ->whereBetween('created_at', [$quarterStart, $quarterEnd])
                ->get();

            $quarterlyData[] = [
                'period' => $quarterStart->format('Y Q').$i + 1,
                'reports_count' => $reports->count(),
                'approval_rate' => $reports->count() > 0
                    ? round(($reports->where('status', 'approved')->count() / $reports->count()) * 100, 1)
                    : 0,
            ];
        }

        // Identify trends
        $trends = [
            'report_volume_trend' => $this->calculateTrend(array_column($quarterlyData, 'reports_count')),
            'quality_trend' => $this->calculateTrend(array_column($quarterlyData, 'approval_rate')),
        ];

        return [
            'quarterly_data' => array_reverse($quarterlyData),
            'trends' => $trends,
            'insights' => $this->generateInsights($trends, $teamMembers),
        ];
    }

    private function calculateTrend(array $data): string
    {
        if (count($data) < 2) {
            return 'stable';
        }

        $recent = array_slice($data, -2);
        $change = $recent[1] - $recent[0];

        if ($change > 0) {
            return 'improving';
        }
        if ($change < 0) {
            return 'declining';
        }

        return 'stable';
    }

    private function generateInsights(array $trends, Collection $teamMembers): array
    {
        $insights = [];

        if ($trends['report_volume_trend'] === 'declining') {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Declining Report Volume',
                'message' => 'Team report submissions have decreased in recent quarters.',
                'action' => 'Consider reviewing workload distribution and reporting requirements.',
            ];
        }

        if ($trends['quality_trend'] === 'declining') {
            $insights[] = [
                'type' => 'warning',
                'title' => 'Quality Concerns',
                'message' => 'Report approval rates have been declining.',
                'action' => 'Consider providing additional training or clearer guidelines.',
            ];
        }

        if ($trends['report_volume_trend'] === 'improving' && $trends['quality_trend'] === 'improving') {
            $insights[] = [
                'type' => 'success',
                'title' => 'Excellent Progress',
                'message' => 'Both report volume and quality are trending upward.',
                'action' => 'Keep up the great work and document successful practices.',
            ];
        }

        return $insights;
    }

    private function getUpcomingReports(Collection $teamMembers): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();

        $upcomingReports = DB::table('user_report_schedules')
            ->whereIn('user_id', $teamIds)
            ->where('user_report_schedules.is_active', true)
            ->where('next_due_at', '<=', now()->addDays(7))
            ->join('users', 'user_report_schedules.user_id', '=', 'users.id')
            ->select([
                'user_report_schedules.*',
                'users.name as user_name',
            ])
            ->orderBy('next_due_at')
            ->get();

        return $upcomingReports->map(function ($schedule) {
            return [
                'user_name' => $schedule->user_name,
                'report_type' => $schedule->report_type,
                'due_at' => Carbon::parse($schedule->next_due_at),
                'is_overdue' => Carbon::parse($schedule->next_due_at)->isPast(),
                'days_until_due' => Carbon::parse($schedule->next_due_at)->diffInDays(now(), false),
            ];
        })->toArray();
    }

    private function getTeamRankings(Collection $teamMembers, array $timeframe): array
    {
        $teamIds = $teamMembers->pluck('id')->toArray();

        $rankings = $teamMembers->map(function ($member) use ($timeframe) {
            $reports = Report::where('user_id', $member->id)
                ->whereBetween('created_at', [
                    $timeframe['current_month']['start'],
                    $timeframe['current_month']['end'],
                ])
                ->get();

            $score = $this->calculateMemberScore($reports);

            return [
                'user_id' => $member->id,
                'name' => $member->name,
                'department' => $member->department?->name,
                'score' => $score,
                'reports_count' => $reports->count(),
                'approval_rate' => $reports->count() > 0
                    ? round(($reports->where('status', 'approved')->count() / $reports->count()) * 100, 1)
                    : 0,
            ];
        })->sortByDesc('score');

        return [
            'rankings' => $rankings->values()->toArray(),
            'top_3' => $rankings->take(3)->values()->toArray(),
        ];
    }

    private function calculateMemberScore(Collection $reports): int
    {
        $score = 0;

        // Base points for each report
        $score += $reports->count() * 10;

        // Bonus points for approved reports
        $score += $reports->where('status', 'approved')->count() * 15;

        // Bonus points for on-time submissions
        $onTimeReports = $reports->filter(function ($report) {
            return $report->submitted_at &&
                   $report->submitted_at->lte($report->created_at->addDays(7));
        });
        $score += $onTimeReports->count() * 5;

        // Penalty for rejected reports
        $score -= $reports->where('status', 'rejected')->count() * 10;

        return max(0, $score); // Ensure score is never negative
    }

    public function exportTeamAnalytics(User $manager, array $options = []): array
    {
        $data = $this->getManagerDashboardData($manager);

        return [
            'export_timestamp' => now()->toISOString(),
            'manager' => $manager->only(['id', 'name', 'email']),
            'team_size' => $data['team_overview']['total_members'],
            'period' => $options['period'] ?? 'current_month',
            'analytics_data' => $data,
        ];
    }
}
