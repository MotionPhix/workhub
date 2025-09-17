<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Invitation;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class AdminDashboardController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the admin dashboard with comprehensive analytics
     */
    public function index(Request $request)
    {
        $stats = $this->getBasicStats();
        $analytics = $this->getAdvancedAnalytics();
        $charts = $this->getChartData();
        $activities = $this->getRecentActivities();
        $insights = $this->getBusinessInsights();
        $projects = $this->getProjectManagementData($request);

        return Inertia::render('admin/dashboard/Index', [
            'stats' => $stats,
            'analytics' => $analytics,
            'charts' => $charts,
            'activities' => $activities,
            'insights' => $insights,
            'projects' => $projects,
            'filters' => $request->only(['project_type', 'status', 'priority', 'search', 'sort_by', 'sort_direction']),
        ]);
    }

    private function getBasicStats(): array
    {
        $now = now();
        $lastMonth = $now->copy()->subMonth();

        $currentUsers = User::count();
        $lastMonthUsers = User::where('created_at', '<=', $lastMonth)->count();
        $userGrowth = $lastMonthUsers > 0 ? (($currentUsers - $lastMonthUsers) / $lastMonthUsers) * 100 : 0;

        return [
            'total_users' => $currentUsers,
            'user_growth' => round($userGrowth, 1),
            'pending_invitations' => Invitation::where('status', 'pending')->count(),
            'work_entries_today' => WorkEntry::whereDate('created_at', today())->count(),
            'active_users' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'total_work_hours_month' => WorkEntry::whereMonth('created_at', now()->month)
                ->whereNotNull('start_date_time')
                ->whereNotNull('end_date_time')
                ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time)) as total_hours')
                ->value('total_hours') ?? 0,
            'avg_productivity_score' => $this->calculateAverageProductivity(),
            'system_health' => $this->getSystemHealth(),
        ];
    }

    private function getAdvancedAnalytics(): array
    {
        return [
            'user_distribution' => $this->getUserDistributionByRole(),
            'productivity_trends' => $this->getProductivityTrends(),
            'department_performance' => $this->getDepartmentPerformance(),
            'invitation_conversion' => $this->getInvitationConversionRate(),
            'peak_activity_hours' => $this->getPeakActivityHours(),
            'user_engagement' => $this->getUserEngagementMetrics(),
        ];
    }

    private function getChartData(): array
    {
        return [
            'user_registration_trend' => $this->getUserRegistrationTrend(),
            'work_entries_trend' => $this->getWorkEntriesTrend(),
            'productivity_by_department' => $this->getProductivityByDepartment(),
            'monthly_hours_comparison' => $this->getMonthlyHoursComparison(),
        ];
    }

    private function getRecentActivities(): array
    {
        return [
            'new_users' => User::latest()->limit(5)->get(['name', 'email', 'created_at'])->toArray(),
            'recent_work_entries' => WorkEntry::with('user:id,name')
                ->latest()
                ->limit(10)
                ->get(['user_id', 'work_title', 'start_date_time', 'end_date_time', 'created_at'])
                ->toArray(),
            'pending_invitations_list' => Invitation::where('status', 'pending')
                ->latest()
                ->limit(5)
                ->get(['email', 'role', 'created_at'])
                ->toArray(),
        ];
    }

    private function getBusinessInsights(): array
    {
        return [
            'recommendations' => $this->generateRecommendations(),
            'alerts' => $this->getSystemAlerts(),
            'kpi_summary' => $this->getKPISummary(),
        ];
    }

    private function getUserDistributionByRole(): array
    {
        return User::select('roles.name as role', DB::raw('count(*) as count'))
            ->join('model_has_roles', 'users.id', '=', 'model_has_roles.model_id')
            ->join('roles', 'model_has_roles.role_id', '=', 'roles.id')
            ->groupBy('roles.name')
            ->get()
            ->map(function ($item) {
                return [
                    'role' => $item->role,
                    'count' => (int) $item->count,
                ];
            })
            ->toArray();
    }

    private function getProductivityTrends(): array
    {
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $hours = WorkEntry::whereDate('created_at', $date)
                ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
                ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))');
            $last30Days->push([
                'date' => $date->format('Y-m-d'),
                'hours' => $hours ?? 0,
                'entries' => WorkEntry::whereDate('created_at', $date)->count(),
            ]);
        }

        return $last30Days->toArray();
    }

    private function getDepartmentPerformance(): array
    {
        return Department::withCount(['users'])
            ->with(['users' => function ($query) {
                $query->withCount(['workEntries' => function ($q) {
                    $q->whereMonth('created_at', now()->month);
                }]);
            }])
            ->get()
            ->map(function ($dept) {
                $totalHours = $dept->users->sum(function ($user) {
                    return WorkEntry::where('user_id', $user->id)
                        ->whereMonth('created_at', now()->month)
                        ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
                        ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;
                });

                return [
                    'name' => $dept->name,
                    'users_count' => $dept->users_count,
                    'total_hours' => $totalHours,
                    'avg_hours_per_user' => $dept->users_count > 0 ? round($totalHours / $dept->users_count, 1) : 0,
                ];
            })
            ->toArray();
    }

    private function getInvitationConversionRate(): array
    {
        $totalInvitations = Invitation::count();
        $acceptedInvitations = Invitation::where('status', 'accepted')->count();
        $conversionRate = $totalInvitations > 0 ? ($acceptedInvitations / $totalInvitations) * 100 : 0;

        return [
            'total_sent' => $totalInvitations,
            'accepted' => $acceptedInvitations,
            'pending' => Invitation::where('status', 'pending')->count(),
            'expired' => Invitation::where('status', 'expired')->count(),
            'conversion_rate' => round($conversionRate, 1),
        ];
    }

    private function getPeakActivityHours(): array
    {
        return WorkEntry::select(DB::raw('HOUR(created_at) as hour'), DB::raw('COUNT(*) as count'))
            ->whereDate('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('HOUR(created_at)'))
            ->orderBy('count', 'desc')
            ->limit(3)
            ->pluck('count', 'hour')
            ->toArray();
    }

    private function getUserEngagementMetrics(): array
    {
        $totalUsers = User::count();

        return [
            'daily_active' => User::whereDate('last_login_at', today())->count(),
            'weekly_active' => User::where('last_login_at', '>=', now()->subDays(7))->count(),
            'monthly_active' => User::where('last_login_at', '>=', now()->subDays(30))->count(),
            'engagement_rate' => $totalUsers > 0 ?
                round((User::where('last_login_at', '>=', now()->subDays(7))->count() / $totalUsers) * 100, 1) : 0,
        ];
    }

    private function getUserRegistrationTrend(): array
    {
        return User::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as count')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->pluck('count', 'date')
            ->toArray();
    }

    private function getWorkEntriesTrend(): array
    {
        return WorkEntry::select(
            DB::raw('DATE(created_at) as date'),
            DB::raw('COUNT(*) as entries'),
            DB::raw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time)) as hours')
        )
            ->where('created_at', '>=', now()->subDays(30))
            ->groupBy(DB::raw('DATE(created_at)'))
            ->orderBy('date')
            ->get()
            ->toArray();
    }

    private function getProductivityByDepartment(): array
    {
        return Department::select('departments.name')
            ->join('users', 'departments.uuid', '=', 'users.department_uuid')
            ->join('work_entries', 'users.id', '=', 'work_entries.user_id')
            ->where('work_entries.created_at', '>=', now()->subDays(30))
            ->groupBy('departments.name')
            ->selectRaw('departments.name, AVG(TIMESTAMPDIFF(HOUR, work_entries.start_date_time, work_entries.end_date_time)) as avg_hours, COUNT(work_entries.id) as total_entries')
            ->get()
            ->toArray();
    }

    private function getMonthlyHoursComparison(): array
    {
        $currentMonth = WorkEntry::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
            ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;

        $lastMonth = WorkEntry::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
            ->value('SUM(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;

        return [
            'current_month' => $currentMonth ?? 0,
            'last_month' => $lastMonth ?? 0,
            'growth_percentage' => $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1) : 0,
        ];
    }

    private function calculateAverageProductivity(): float
    {
        $avgHoursPerUser = WorkEntry::whereMonth('created_at', now()->month)
            ->selectRaw('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))')
            ->value('AVG(TIMESTAMPDIFF(HOUR, start_date_time, end_date_time))') ?? 0;

        // Simple productivity score based on hours (could be more sophisticated)
        return $avgHoursPerUser ? round(min($avgHoursPerUser * 12.5, 100), 1) : 0;
    }

    private function getSystemHealth(): array
    {
        $errors = 0; // You could check logs, failed jobs, etc.
        $warnings = 0;

        // Check for potential issues
        if (User::where('last_login_at', '<', now()->subDays(30))->count() > 0) {
            $warnings++;
        }

        if (Invitation::where('status', 'pending')->where('created_at', '<', now()->subDays(7))->count() > 0) {
            $warnings++;
        }

        return [
            'status' => $errors > 0 ? 'error' : ($warnings > 0 ? 'warning' : 'healthy'),
            'errors' => $errors,
            'warnings' => $warnings,
        ];
    }

    private function generateRecommendations(): array
    {
        $recommendations = [];

        // Check for inactive users
        $inactiveUsers = User::where('last_login_at', '<', now()->subDays(30))->count();
        if ($inactiveUsers > 0) {
            $recommendations[] = "You have {$inactiveUsers} inactive users. Consider reaching out for re-engagement.";
        }

        // Check pending invitations
        $oldPendingInvitations = Invitation::where('status', 'pending')
            ->where('created_at', '<', now()->subDays(7))->count();
        if ($oldPendingInvitations > 0) {
            $recommendations[] = "You have {$oldPendingInvitations} pending invitations older than 7 days. Consider resending them.";
        }

        // Check productivity
        $avgProductivity = $this->calculateAverageProductivity();
        if ($avgProductivity < 60) {
            $recommendations[] = 'Overall productivity is below average. Consider reviewing team workload and processes.';
        }

        return $recommendations;
    }

    private function getSystemAlerts(): array
    {
        $alerts = [];

        // System health alerts
        $systemHealth = $this->getSystemHealth();
        if ($systemHealth['status'] === 'error') {
            $alerts[] = [
                'type' => 'error',
                'message' => 'System errors detected. Please check system logs.',
                'action' => 'View Logs',
            ];
        }

        return $alerts;
    }

    private function getKPISummary(): array
    {
        return [
            'user_satisfaction' => 85.5, // This would come from surveys/feedback
            'system_uptime' => 99.9,
            'data_accuracy' => 98.2,
            'response_time' => '120ms', // Average API response time
        ];
    }

    private function getProjectManagementData(?Request $request = null): array
    {
        return [
            'project_overview' => $this->getProjectOverview(),
            'project_boards' => $this->getProjectBoards($request),
            'workload_distribution' => $this->getWorkloadDistribution(),
            'project_timeline' => $this->getProjectTimeline(),
            'team_assignments' => $this->getTeamAssignments(),
            'project_health' => $this->getProjectHealth(),
        ];
    }

    private function getProjectOverview(): array
    {
        $totalProjects = Project::count();
        $activeProjects = Project::active()->count();
        $completedProjects = Project::where('status', 'completed')->count();
        $overdueProjects = Project::overdue()->count();
        $clientProjects = Project::clientProjects()->count();
        $internalProjects = Project::internalProjects()->count();

        return [
            'total_projects' => $totalProjects,
            'active_projects' => $activeProjects,
            'completed_projects' => $completedProjects,
            'overdue_projects' => $overdueProjects,
            'client_projects' => $clientProjects,
            'internal_projects' => $internalProjects,
            'completion_rate' => $totalProjects > 0 ? round(($completedProjects / $totalProjects) * 100, 1) : 0,
            'on_time_delivery' => $this->getOnTimeDeliveryRate(),
        ];
    }

    private function getProjectBoards(?Request $request = null): array
    {
        $query = Project::with(['manager:id,name', 'department:uuid,name'])
            ->withCount(['workEntries as work_entries_count']);

        // Apply filters if request is provided
        if ($request) {
            // Filter by project type
            if ($request->filled('project_type') && $request->project_type !== 'all') {
                if ($request->project_type === 'client') {
                    $query->clientProjects();
                } elseif ($request->project_type === 'internal') {
                    $query->internalProjects();
                }
            }

            // Filter by status
            if ($request->filled('status') && $request->status !== 'all') {
                if ($request->status === 'active') {
                    $query->active();
                } else {
                    $query->where('status', $request->status);
                }
            } else {
                // Default to active projects if no status filter
                $query->active();
            }

            // Filter by priority
            if ($request->filled('priority') && $request->priority !== 'all') {
                $query->where('priority', $request->priority);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('description', 'LIKE', "%{$search}%")
                        ->orWhere('client_name', 'LIKE', "%{$search}%");
                });
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'name');
            $sortDirection = $request->get('sort_direction', 'asc');

            switch ($sortBy) {
                case 'due_date':
                    $query->orderBy('due_date', $sortDirection);
                    break;
                case 'priority':
                    $query->orderByRaw("FIELD(priority, 'urgent', 'high', 'medium', 'low') ".($sortDirection === 'desc' ? 'DESC' : 'ASC'));
                    break;
                case 'completion':
                    $query->orderBy('completion_percentage', $sortDirection);
                    break;
                default:
                    $query->orderBy('name', $sortDirection);
            }
        } else {
            // Default behavior - show active projects
            $query->active();
        }

        return [
            'active_projects' => $query
                ->get()
                ->map(function ($project) {
                    return [
                        'uuid' => $project->uuid,
                        'name' => $project->name,
                        'description' => $project->description,
                        'status' => $project->status,
                        'priority' => $project->priority,
                        'project_type' => $project->project_type,
                        'client_name' => $project->client_name,
                        'manager' => $project->manager,
                        'department' => $project->department,
                        'start_date' => $project->start_date,
                        'due_date' => $project->due_date,
                        'completion_percentage' => $project->completion_percentage,
                        'estimated_hours' => $project->estimated_hours,
                        'actual_hours' => $project->actual_hours,
                        'work_entries_count' => $project->work_entries_count,
                        'team_members_count' => $project->getTeamMembersCount(),
                        'is_overdue' => $project->isOverdue(),
                        'has_active_work' => $project->hasActiveWork(),
                        'efficiency_rate' => $project->getEfficiencyRate(),
                    ];
                })
                ->toArray(),
            'recent_projects' => Project::orderBy('created_at', 'desc')
                ->limit(10)
                ->with(['manager:id,name', 'department:uuid,name'])
                ->get()
                ->toArray(),
        ];
    }

    private function getWorkloadDistribution(): array
    {
        $departmentWorkload = Department::with(['users'])
            ->get()
            ->map(function ($dept) {
                $activeProjects = Project::where('department_uuid', $dept->uuid)
                    ->active()
                    ->count();

                $totalHours = Project::where('department_uuid', $dept->uuid)
                    ->sum('actual_hours');

                $avgCompletionRate = Project::where('department_uuid', $dept->uuid)
                    ->avg('completion_percentage');

                return [
                    'department' => $dept->name,
                    'users_count' => $dept->users->count(),
                    'active_projects' => $activeProjects,
                    'total_hours' => round($totalHours, 1),
                    'avg_completion_rate' => round($avgCompletionRate ?? 0, 1),
                    'workload_status' => $this->getWorkloadStatus($activeProjects, $dept->users->count()),
                ];
            })
            ->toArray();

        return [
            'by_department' => $departmentWorkload,
            'by_user' => $this->getUserWorkloadDistribution(),
            'capacity_analysis' => $this->getCapacityAnalysis(),
        ];
    }

    private function getProjectTimeline(): array
    {
        $upcomingDeadlines = Project::active()
            ->where('due_date', '>=', now())
            ->where('due_date', '<=', now()->addDays(30))
            ->orderBy('due_date')
            ->with(['manager:id,name', 'department:uuid,name'])
            ->get()
            ->map(function ($project) {
                return [
                    'uuid' => $project->uuid,
                    'name' => $project->name,
                    'due_date' => $project->due_date,
                    'priority' => $project->priority,
                    'completion_percentage' => $project->completion_percentage,
                    'manager' => $project->manager,
                    'department' => $project->department,
                    'days_remaining' => now()->diffInDays($project->due_date),
                    'at_risk' => $project->completion_percentage < 50 && now()->diffInDays($project->due_date) <= 7,
                ];
            })
            ->toArray();

        return [
            'upcoming_deadlines' => $upcomingDeadlines,
            'overdue_projects' => Project::overdue()
                ->with(['manager:id,name', 'department:uuid,name'])
                ->get()
                ->toArray(),
            'milestones' => $this->getProjectMilestones(),
        ];
    }

    private function getTeamAssignments(): array
    {
        $userAssignments = User::with(['department:uuid,name'])
            ->withCount(['workEntries as active_work_entries' => function ($query) {
                $query->whereHas('project', function ($q) {
                    $q->active();
                });
            }])
            ->get()
            ->map(function ($user) {
                $activeProjects = Project::whereHas('workEntries', function ($query) use ($user) {
                    $query->where('user_id', $user->id);
                })
                    ->active()
                    ->get();

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'department' => $user->department,
                    'active_projects_count' => $activeProjects->count(),
                    'active_work_entries' => $user->active_work_entries,
                    'current_projects' => $activeProjects->pluck('name')->toArray(),
                    'workload_status' => $this->getUserWorkloadStatus($activeProjects->count()),
                ];
            })
            ->toArray();

        return [
            'user_assignments' => $userAssignments,
            'unassigned_projects' => Project::active()
                ->whereDoesntHave('workEntries')
                ->count(),
            'team_utilization' => $this->getTeamUtilization(),
        ];
    }

    private function getProjectHealth(): array
    {
        $projects = Project::active()->get();
        $healthyProjects = $projects->filter(function ($project) {
            return $project->completion_percentage >= 25 &&
                   ! $project->isOverdue() &&
                   $project->hasActiveWork();
        })->count();

        $atRiskProjects = $projects->filter(function ($project) {
            return $project->completion_percentage < 25 ||
                   ($project->isOverdue() && $project->completion_percentage < 100) ||
                   ! $project->hasActiveWork();
        })->count();

        return [
            'healthy_projects' => $healthyProjects,
            'at_risk_projects' => $atRiskProjects,
            'blocked_projects' => Project::where('status', 'on_hold')->count(),
            'health_score' => $projects->count() > 0 ?
                round(($healthyProjects / $projects->count()) * 100, 1) : 100,
            'common_issues' => $this->identifyCommonProjectIssues(),
        ];
    }

    // Helper methods for project management
    private function getOnTimeDeliveryRate(): float
    {
        $completedProjects = Project::where('status', 'completed')->count();
        if ($completedProjects === 0) {
            return 100;
        }

        $onTimeProjects = Project::where('status', 'completed')
            ->whereRaw('updated_at <= due_date')
            ->count();

        return round(($onTimeProjects / $completedProjects) * 100, 1);
    }

    private function getWorkloadStatus(int $projects, int $users): string
    {
        if ($users === 0) {
            return 'no_capacity';
        }
        $ratio = $projects / $users;

        if ($ratio > 3) {
            return 'overloaded';
        }
        if ($ratio > 2) {
            return 'high';
        }
        if ($ratio > 1) {
            return 'medium';
        }

        return 'low';
    }

    private function getUserWorkloadDistribution(): array
    {
        return User::select('users.id', 'users.name', 'departments.name as department_name')
            ->leftJoin('departments', 'users.department_uuid', '=', 'departments.uuid')
            ->leftJoin('work_entries', 'users.id', '=', 'work_entries.user_id')
            ->leftJoin('projects', 'work_entries.project_uuid', '=', 'projects.uuid')
            ->where('projects.status', 'active')
            ->orWhereNull('projects.status')
            ->groupBy('users.id', 'users.name', 'departments.name')
            ->selectRaw('COUNT(DISTINCT projects.uuid) as active_projects_count')
            ->selectRaw('SUM(TIMESTAMPDIFF(HOUR, work_entries.start_date_time, work_entries.end_date_time)) as total_hours')
            ->get()
            ->toArray();
    }

    private function getCapacityAnalysis(): array
    {
        $totalUsers = User::count();
        $activeUsers = User::whereHas('workEntries', function ($query) {
            $query->whereHas('project', function ($q) {
                $q->active();
            });
        })->count();

        return [
            'total_users' => $totalUsers,
            'active_users' => $activeUsers,
            'utilization_rate' => $totalUsers > 0 ? round(($activeUsers / $totalUsers) * 100, 1) : 0,
            'available_capacity' => $totalUsers - $activeUsers,
        ];
    }

    private function getProjectMilestones(): array
    {
        // This could be extended to include actual milestone tracking
        return [
            'upcoming_milestones' => [],
            'completed_milestones' => [],
        ];
    }

    private function getUserWorkloadStatus(int $projectCount): string
    {
        if ($projectCount > 5) {
            return 'overloaded';
        }
        if ($projectCount > 3) {
            return 'high';
        }
        if ($projectCount > 1) {
            return 'medium';
        }
        if ($projectCount === 1) {
            return 'normal';
        }

        return 'available';
    }

    private function getTeamUtilization(): array
    {
        $departments = Department::withCount([
            'users',
            'users as active_users' => function ($query) {
                $query->whereHas('workEntries', function ($q) {
                    $q->whereHas('project', function ($p) {
                        $p->active();
                    });
                });
            },
        ])->get();

        return $departments->map(function ($dept) {
            return [
                'department' => $dept->name,
                'total_users' => $dept->users_count,
                'active_users' => $dept->active_users,
                'utilization_rate' => $dept->users_count > 0 ?
                    round(($dept->active_users / $dept->users_count) * 100, 1) : 0,
            ];
        })->toArray();
    }

    private function identifyCommonProjectIssues(): array
    {
        $issues = [];

        // Check for projects without recent activity
        $inactiveProjects = Project::active()
            ->whereDoesntHave('workEntries', function ($query) {
                $query->where('created_at', '>=', now()->subDays(7));
            })
            ->count();

        if ($inactiveProjects > 0) {
            $issues[] = "Projects with no recent activity: {$inactiveProjects}";
        }

        // Check for overdue projects
        $overdueCount = Project::overdue()->count();
        if ($overdueCount > 0) {
            $issues[] = "Overdue projects: {$overdueCount}";
        }

        // Check for projects without team members
        $unassignedProjects = Project::active()
            ->whereDoesntHave('workEntries')
            ->count();

        if ($unassignedProjects > 0) {
            $issues[] = "Projects without team assignments: {$unassignedProjects}";
        }

        return $issues;
    }

    /**
     * Show admin profile page
     */
    public function profile(Request $request)
    {
        return Inertia::render('admin/profile/Index', [
            'user' => Auth::user()->load(['roles', 'permissions']),
        ]);
    }

    /**
     * Show admin settings page
     */
    public function settings(Request $request)
    {
        return Inertia::render('admin/profile/Settings', [
            'user' => Auth::user(),
            'preferences' => Auth::user()->preferences ?? [],
        ]);
    }

    /**
     * Update admin settings
     */
    public function updateSettings(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.Auth::id()],
            'preferences' => ['nullable', 'array'],
        ]);

        Auth::user()->update($validated);

        return back()->with('success', 'Settings updated successfully.');
    }
}
