<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Invitation;
use App\Models\User;
use App\Models\WorkEntry;
use App\Models\Department;
use App\Models\Report;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Carbon\Carbon;

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

        return Inertia::render('admin/dashboard/Index', [
            'stats' => $stats,
            'analytics' => $analytics,
            'charts' => $charts,
            'activities' => $activities,
            'insights' => $insights,
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
            'total_work_hours_month' => WorkEntry::whereMonth('created_at', now()->month)->sum('hours_worked') ?? 0,
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
                ->get(['user_id', 'work_title', 'hours_worked', 'created_at'])
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
            ->map(function($item) {
                return [
                    'role' => $item->role,
                    'count' => (int) $item->count
                ];
            })
            ->toArray();
    }

    private function getProductivityTrends(): array
    {
        $last30Days = collect();
        for ($i = 29; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $hours = WorkEntry::whereDate('created_at', $date)->sum('hours_worked');
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
            ->with(['users' => function($query) {
                $query->withCount(['workEntries' => function($q) {
                    $q->whereMonth('created_at', now()->month);
                }]);
            }])
            ->get()
            ->map(function($dept) {
                $totalHours = $dept->users->sum(function($user) {
                    return WorkEntry::where('user_id', $user->id)
                        ->whereMonth('created_at', now()->month)
                        ->sum('hours_worked');
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
                DB::raw('SUM(hours_worked) as hours')
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
            ->selectRaw('departments.name, AVG(work_entries.hours_worked) as avg_hours, COUNT(work_entries.id) as total_entries')
            ->get()
            ->toArray();
    }

    private function getMonthlyHoursComparison(): array
    {
        $currentMonth = WorkEntry::whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->sum('hours_worked');

        $lastMonth = WorkEntry::whereMonth('created_at', now()->subMonth()->month)
            ->whereYear('created_at', now()->subMonth()->year)
            ->sum('hours_worked');

        return [
            'current_month' => $currentMonth ?? 0,
            'last_month' => $lastMonth ?? 0,
            'growth_percentage' => $lastMonth > 0 ? round((($currentMonth - $lastMonth) / $lastMonth) * 100, 1) : 0,
        ];
    }

    private function calculateAverageProductivity(): float
    {
        $avgHoursPerUser = WorkEntry::whereMonth('created_at', now()->month)
            ->avg('hours_worked');

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
            $recommendations[] = "Overall productivity is below average. Consider reviewing team workload and processes.";
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
                'action' => 'View Logs'
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
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . Auth::id()],
            'preferences' => ['nullable', 'array'],
        ]);

        Auth::user()->update($validated);

        return back()->with('success', 'Settings updated successfully.');
    }
}
