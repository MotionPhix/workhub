<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkEntry;
use App\Services\Analytics\ManagerAnalyticsService;
use App\Services\Report\ReportSchedulingService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ManagerDashboardController extends Controller
{
    public function __construct(
        private ManagerAnalyticsService $analyticsService,
        private ReportSchedulingService $schedulingService
    ) {}

    public function index(Request $request)
    {
        Gate::authorize('access-manager-dashboard');

        $dashboardData = $this->analyticsService->getManagerDashboardData(auth()->user());

        // Get real chart data for the last 7 days
        $chartData = $this->getChartData();

        return Inertia::render('manager/Dashboard', [
            'dashboardData' => $dashboardData,
            'chartData' => $chartData,
            'currentUser' => auth()->user(),
        ]);
    }

    public function teamReports(Request $request)
    {
        Gate::authorize('view-team-reports');

        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'status' => 'nullable|in:draft,pending,approved,rejected,sent',
            'type' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
        ]);

        $teamMembers = User::where('manager_email', auth()->user()->email)->pluck('id');

        $query = Report::whereIn('user_id', $teamMembers)
            ->with(['user', 'department'])
            ->orderByDesc('created_at');

        if (! empty($filters['start_date'])) {
            $query->where('created_at', '>=', $filters['start_date']);
        }

        if (! empty($filters['end_date'])) {
            $query->where('created_at', '<=', $filters['end_date']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['type'])) {
            $query->where('report_type', $filters['type']);
        }

        if (! empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }

        $reports = $query->paginate(15);

        return Inertia::render('manager/TeamReports', [
            'reports' => $reports,
            'filters' => $filters,
            'teamMembers' => User::where('manager_email', auth()->user()->email)
                ->select('id', 'name')
                ->get(),
        ]);
    }

    public function approveReport(Request $request, Report $report)
    {
        Gate::authorize('approve-report', $report);

        $request->validate([
            'comments' => 'nullable|string|max:500',
        ]);

        $report->approve(auth()->user());

        if ($request->comments) {
            $report->update([
                'settings' => array_merge($report->settings ?? [], [
                    'approval_comments' => $request->comments,
                ]),
            ]);
        }

        return redirect()->back()->with('success', 'Report approved successfully.');
    }

    public function rejectReport(Request $request, Report $report)
    {
        Gate::authorize('approve-report', $report);

        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $report->reject(auth()->user(), $request->reason);

        return redirect()->back()->with('success', 'Report rejected with feedback.');
    }

    public function teamPerformance(Request $request)
    {
        Gate::authorize('view-team-performance');

        $period = $request->get('period', 'current_month');
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        // Get performance data based on work entries
        $performanceData = $this->getTeamPerformanceData($teamMemberIds);
        $complianceData = $this->getComplianceData($teamMemberIds);
        $trendingData = $this->getTrendingData($teamMemberIds);
        $chartData = $this->getPerformanceChartData($teamMemberIds);

        return Inertia::render('manager/team/Performance', [
            'performanceData' => $performanceData,
            'complianceData' => $complianceData,
            'trendingData' => $trendingData,
            'chartData' => $chartData,
            'period' => $period,
        ]);
    }

    public function teamSchedules(Request $request)
    {
        Gate::authorize('manage-team-schedules');

        $teamMembers = User::where('manager_email', auth()->user()->email)->get();
        $schedules = [];

        foreach ($teamMembers as $member) {
            $memberSchedules = $this->schedulingService->getUpcomingReports($member, 30);
            $schedules[$member->id] = [
                'user' => $member->only(['id', 'name', 'email']),
                'schedules' => $memberSchedules,
                'stats' => $this->schedulingService->getUserScheduleStats($member),
            ];
        }

        return Inertia::render('manager/TeamSchedules', [
            'schedules' => $schedules,
            'upcomingReports' => collect($schedules)
                ->flatMap(fn ($data) => $data['schedules'])
                ->sortBy('due_at')
                ->take(10)
                ->values(),
        ]);
    }

    public function exportAnalytics(Request $request)
    {
        Gate::authorize('export-team-analytics');

        $options = $request->validate([
            'period' => 'nullable|in:current_month,last_month,current_quarter,last_quarter',
            'format' => 'nullable|in:json,csv,xlsx',
        ]);

        $data = $this->analyticsService->exportTeamAnalytics(auth()->user(), $options);

        switch ($options['format'] ?? 'json') {
            case 'csv':
                return response()->streamDownload(function () use ($data) {
                    $output = fopen('php://output', 'w');

                    // Write headers
                    fputcsv($output, ['Metric', 'Value']);

                    // Flatten data for CSV
                    $this->flattenArrayForCsv($data, $output);

                    fclose($output);
                }, 'team-analytics-'.now()->format('Y-m-d').'.csv');

            case 'xlsx':
                // Would require maatwebsite/excel implementation
                return response()->json(['message' => 'Excel export not yet implemented'], 501);

            default:
                return response()->json($data);
        }
    }

    private function flattenArrayForCsv(array $data, $output, string $prefix = ''): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;

            if (is_array($value)) {
                $this->flattenArrayForCsv($value, $output, $fullKey);
            } else {
                fputcsv($output, [$fullKey, $value]);
            }
        }
    }

    private function getChartData(): array
    {
        $teamMemberIds = \App\Models\User::where('manager_email', auth()->user()->email)->pluck('id');

        // Get the last 7 days of data
        $days = [];
        $performanceTrends = [];
        $activityData = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $days[] = $date->format('M j');

            // Calculate productivity for this day
            $dayWorkEntries = \App\Models\WorkEntry::whereIn('user_id', $teamMemberIds)
                ->whereDate('start_date_time', $date)
                ->get();

            $totalHours = $dayWorkEntries->sum('hours_worked');
            $targetHours = $teamMemberIds->count() * 8; // Assuming 8 hours per person target
            $productivity = $targetHours > 0 ? min(100, ($totalHours / $targetHours) * 100) : 0;

            // Calculate efficiency (completed vs total tasks)
            $completedEntries = $dayWorkEntries->where('status', 'completed')->count();
            $totalEntries = $dayWorkEntries->count();
            $efficiency = $totalEntries > 0 ? ($completedEntries / $totalEntries) * 100 : 0;

            $performanceTrends[] = [
                'productivity' => round($productivity, 1),
                'efficiency' => round($efficiency, 1)
            ];

            // Activity data for bar chart
            $loggedHours = round($totalHours, 1);
            $overtimeHours = max(0, $totalHours - ($teamMemberIds->count() * 8));
            $missedHours = max(0, ($teamMemberIds->count() * 8) - $totalHours);

            $activityData[] = [
                'logged' => $loggedHours,
                'overtime' => round($overtimeHours, 1),
                'missed' => round($missedHours, 1)
            ];
        }

        return [
            'days' => $days,
            'performance_trends' => $performanceTrends,
            'activity_data' => $activityData
        ];
    }

    public function bulkApproveReports(Request $request)
    {
        Gate::authorize('bulk-approve-reports');

        $request->validate([
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'exists:reports,id',
            'comments' => 'nullable|string|max:500',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        $reports = Report::whereIn('id', $request->report_ids)
            ->whereIn('user_id', $teamMemberIds)
            ->where('status', 'pending')
            ->get();

        $approvedCount = 0;
        foreach ($reports as $report) {
            if (Gate::allows('approve-report', $report)) {
                $report->approve(auth()->user());

                if ($request->comments) {
                    $report->update([
                        'settings' => array_merge($report->settings ?? [], [
                            'approval_comments' => $request->comments,
                        ]),
                    ]);
                }
                $approvedCount++;
            }
        }

        return redirect()->back()->with('success', "Successfully approved {$approvedCount} reports.");
    }

    private function getTeamPerformanceData($teamMemberIds)
    {
        $currentMonth = now()->startOfMonth();
        $lastMonth = now()->subMonth()->startOfMonth();

        // Current month work entries
        $currentEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', $currentMonth)
            ->get();

        // Last month work entries
        $lastMonthEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('start_date_time', [$lastMonth, $currentMonth])
            ->get();

        // Calculate metrics
        $totalHours = $currentEntries->sum('hours_worked');
        $totalEntries = $currentEntries->count();
        $avgHoursPerMember = $teamMemberIds->count() > 0 ? round($totalHours / $teamMemberIds->count(), 1) : 0;

        // Calculate productivity (based on hours and entry completion)
        $targetHoursPerMember = 160; // ~40 hours per week for a month
        $actualHours = $avgHoursPerMember;
        $productivity = min(100, round(($actualHours / $targetHoursPerMember) * 100, 1));

        // Calculate efficiency (based on project completion and status)
        $completedProjects = $currentEntries->where('status', 'completed')->count();
        $efficiency = $totalEntries > 0 ? round(($completedProjects / $totalEntries) * 100, 1) : 0;

        // Overall score (weighted average)
        $overallScore = round(($productivity * 0.4) + ($efficiency * 0.6), 1);

        // Calculate changes from last month
        $lastMonthHours = $lastMonthEntries->sum('hours_worked');
        $lastMonthAvgHours = $teamMemberIds->count() > 0 ? $lastMonthHours / $teamMemberIds->count() : 0;
        $hoursChange = $lastMonthAvgHours > 0 ? round((($avgHoursPerMember - $lastMonthAvgHours) / $lastMonthAvgHours) * 100, 1) : 0;

        return [
            'overall_score' => $overallScore,
            'team_productivity' => $productivity,
            'efficiency_score' => $efficiency,
            'avg_hours_per_member' => $avgHoursPerMember,
            'total_hours' => $totalHours,
            'total_entries' => $totalEntries,
            'hours_change_percent' => $hoursChange > 0 ? "+{$hoursChange}%" : "{$hoursChange}%",
        ];
    }

    private function getComplianceData($teamMemberIds)
    {
        $oneWeekAgo = now()->subWeek();
        $twoWeeksAgo = now()->subWeeks(2);

        // Get recent work entries
        $recentEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', $oneWeekAgo)
            ->get();

        // Count members with recent entries
        $membersWithRecentWork = $recentEntries->pluck('user_id')->unique()->count();
        $totalMembers = $teamMemberIds->count();

        // Calculate compliance rate
        $complianceRate = $totalMembers > 0 ? round(($membersWithRecentWork / $totalMembers) * 100, 1) : 0;

        // On-time submissions (entries created within reasonable time of work completion)
        $onTimeSubmissions = $recentEntries->filter(function($entry) {
            $workDate = Carbon::parse($entry->start_date_time);
            $createdDate = Carbon::parse($entry->created_at);
            return $createdDate->diffInDays($workDate) <= 2; // Submitted within 2 days of work
        })->count();

        // Overdue count (members without recent work entries)
        $overdueCount = $totalMembers - $membersWithRecentWork;

        return [
            'compliance_rate' => $complianceRate,
            'on_time_submissions' => $onTimeSubmissions,
            'overdue_count' => $overdueCount,
            'total_members' => $totalMembers,
            'active_members' => $membersWithRecentWork,
        ];
    }

    private function getTrendingData($teamMemberIds)
    {
        // Get top performers based on hours and project completion
        $topPerformers = User::whereIn('id', $teamMemberIds)
            ->with(['workEntries' => function($query) {
                $query->whereDate('start_date_time', '>=', now()->startOfMonth());
            }])
            ->get()
            ->map(function($user) {
                $entries = $user->workEntries;
                $totalHours = $entries->sum('hours_worked');
                $completedWork = $entries->where('status', 'completed')->count();
                $totalWork = $entries->count();

                $completionRate = $totalWork > 0 ? ($completedWork / $totalWork) * 100 : 0;
                $score = ($totalHours * 0.6) + ($completionRate * 0.4);

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'department' => $user->department?->name ?? 'General',
                    'hours' => round($totalHours, 1),
                    'score' => round($score, 1),
                    'completion_rate' => round($completionRate, 1),
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->toArray();

        // Improvement areas based on team performance
        $improvementAreas = [
            [
                'name' => 'Time Management',
                'score' => $this->calculateTimeManagementScore($teamMemberIds),
            ],
            [
                'name' => 'Project Completion',
                'score' => $this->calculateCompletionScore($teamMemberIds),
            ],
            [
                'name' => 'Consistency',
                'score' => $this->calculateConsistencyScore($teamMemberIds),
            ],
            [
                'name' => 'Quality',
                'score' => $this->calculateQualityScore($teamMemberIds),
            ],
        ];

        return [
            'top_performers' => $topPerformers,
            'improvement_areas' => $improvementAreas,
        ];
    }

    private function getPerformanceChartData($teamMemberIds)
    {
        $weeks = [];
        $productivityData = [];
        $efficiencyData = [];
        $qualityData = [];

        // Get data for last 4 weeks
        for ($i = 3; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();
            $weeks[] = $weekStart->format('M d');

            $weekEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
                ->whereBetween('start_date_time', [$weekStart, $weekEnd])
                ->get();

            $totalHours = $weekEntries->sum('hours_worked');
            $targetHours = $teamMemberIds->count() * 40; // 40 hours per person per week
            $productivity = $targetHours > 0 ? min(100, round(($totalHours / $targetHours) * 100)) : 0;

            $completedWork = $weekEntries->where('status', 'completed')->count();
            $totalWork = $weekEntries->count();
            $efficiency = $totalWork > 0 ? round(($completedWork / $totalWork) * 100) : 0;

            // Quality based on work without issues/revisions
            $qualityWork = $weekEntries->where('outcome', '!=', 'needs_revision')->count();
            $quality = $totalWork > 0 ? round(($qualityWork / $totalWork) * 100) : 0;

            $productivityData[] = $productivity;
            $efficiencyData[] = $efficiency;
            $qualityData[] = $quality;
        }

        return [
            'categories' => $weeks,
            'series' => [
                [
                    'name' => 'Productivity',
                    'data' => $productivityData,
                ],
                [
                    'name' => 'Efficiency',
                    'data' => $efficiencyData,
                ],
                [
                    'name' => 'Quality',
                    'data' => $qualityData,
                ],
            ],
        ];
    }

    private function calculateTimeManagementScore($teamMemberIds)
    {
        $entries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', now()->startOfMonth())
            ->get();

        $onTimeEntries = $entries->filter(function($entry) {
            $workDate = Carbon::parse($entry->start_date_time);
            $createdDate = Carbon::parse($entry->created_at);
            return $createdDate->diffInDays($workDate) <= 1;
        })->count();

        return $entries->count() > 0 ? round(($onTimeEntries / $entries->count()) * 100) : 75;
    }

    private function calculateCompletionScore($teamMemberIds)
    {
        $entries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', now()->startOfMonth())
            ->get();

        $completedEntries = $entries->where('status', 'completed')->count();
        return $entries->count() > 0 ? round(($completedEntries / $entries->count()) * 100) : 80;
    }

    private function calculateConsistencyScore($teamMemberIds)
    {
        // Check how consistently team members log work
        $dailyEntries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', now()->startOfMonth())
            ->groupBy('user_id')
            ->selectRaw('user_id, COUNT(DISTINCT DATE(start_date_time)) as active_days')
            ->get();

        $workingDaysInMonth = now()->diffInDaysFiltered(function($date) {
            return $date->isWeekday();
        }, now()->startOfMonth());

        $avgActiveDays = $dailyEntries->avg('active_days') ?: 0;
        return $workingDaysInMonth > 0 ? round(($avgActiveDays / $workingDaysInMonth) * 100) : 85;
    }

    private function calculateQualityScore($teamMemberIds)
    {
        $entries = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', now()->startOfMonth())
            ->get();

        $qualityEntries = $entries->where('outcome', '!=', 'needs_revision')->count();
        return $entries->count() > 0 ? round(($qualityEntries / $entries->count()) * 100) : 90;
    }

    public function profile()
    {
        $user = auth()->user();
        $teamMemberIds = User::where('manager_email', $user->email)->pluck('id')->toArray();

        // Get team statistics
        $teamStats = [
            'total_members' => count($teamMemberIds),
            'active_projects' => $this->getActiveProjectsCount($teamMemberIds),
            'pending_approvals' => $this->getPendingApprovalsCount($teamMemberIds),
            'team_performance_score' => $this->getTeamPerformanceScore($teamMemberIds),
        ];

        // Get recent activity
        $recentActivity = $this->getManagerRecentActivity($user->id);

        return Inertia::render('manager/profile/Index', [
            'user' => $user->only([
                'id', 'name', 'email', 'phone', 'address', 'bio', 'avatar',
                'created_at', 'email_verified_at', 'role', 'manager_email',
                'department', 'position'
            ]),
            'teamStats' => $teamStats,
            'recentActivity' => $recentActivity,
        ]);
    }

    public function updateProfile(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . auth()->id(),
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:500',
            'bio' => 'nullable|string|max:1000',
            'position' => 'nullable|string|max:100',
            'department' => 'nullable|string|max:100',
        ]);

        auth()->user()->update($validated);

        return redirect()->back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required|current_password',
            'password' => 'required|min:8|confirmed',
        ]);

        auth()->user()->update([
            'password' => Hash::make($validated['password'])
        ]);

        return redirect()->back()->with('success', 'Password updated successfully.');
    }

    private function getActiveProjectsCount($teamMemberIds): int
    {
        return Project::whereHas('workEntries', function ($query) use ($teamMemberIds) {
            $query->whereIn('user_id', $teamMemberIds)
                  ->whereDate('start_date_time', '>=', now()->startOfMonth());
        })->count();
    }

    private function getPendingApprovalsCount($teamMemberIds): int
    {
        return WorkEntry::whereIn('user_id', $teamMemberIds)
            ->where('status', 'draft')
            ->count();
    }

    private function getTeamPerformanceScore($teamMemberIds): int
    {
        if (empty($teamMemberIds)) {
            return 0;
        }

        $totalHours = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereDate('start_date_time', '>=', now()->startOfMonth())
            ->sum('hours_worked');

        $expectedHours = count($teamMemberIds) * 160; // Assuming 160 hours per month per person

        return $expectedHours > 0 ? min(100, round(($totalHours / $expectedHours) * 100)) : 0;
    }

    private function getManagerRecentActivity($managerId): array
    {
        // Get recent activities for the manager
        $activities = [];

        // Add work entry approvals
        $recentApprovals = WorkEntry::whereHas('user', function ($query) use ($managerId) {
            $query->where('manager_email', User::find($managerId)->email);
        })
        ->where('status', 'completed')
        ->whereDate('updated_at', '>=', now()->subDays(7))
        ->with('user', 'project')
        ->latest('updated_at')
        ->limit(5)
        ->get();

        foreach ($recentApprovals as $approval) {
            $activities[] = [
                'id' => $approval->id,
                'type' => 'approval',
                'description' => "Approved work entry for {$approval->user->name} on {$approval->project->name}",
                'created_at' => $approval->updated_at->toISOString(),
            ];
        }

        // Sort by date and limit
        usort($activities, function ($a, $b) {
            return strtotime($b['created_at']) - strtotime($a['created_at']);
        });

        return array_slice($activities, 0, 10);
    }
}
