<?php

namespace App\Http\Controllers;

use App\Models\WorkEntry;
use App\Services\DashboardService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function index(Request $request)
    {
        $user = Auth::user();

        // Redirect users to their role-appropriate dashboards
        if ($user->hasRole('admin')) {
            return redirect()->route('admin.dashboard');
        }

        if ($user->hasRole('manager')) {
            return redirect()->route('manager.dashboard');
        }

        // Simplified employee dashboard data - calculate directly instead of using service
        $userId = $user->id;

        // Basic work entry stats using the new schema
        $totalWorkEntries = WorkEntry::where('user_id', $userId)->count();

        // Calculate total hours worked using the new computed property
        $totalHoursWorked = WorkEntry::where('user_id', $userId)
            ->get()
            ->sum('hours_worked'); // This uses the computed property

        // Recent entries with basic info
        $recentEntries = WorkEntry::where('user_id', $userId)
            ->latest('start_date_time')
            ->limit(5)
            ->get(['uuid', 'start_date_time', 'end_date_time', 'work_title', 'status'])
            ->map(function ($entry) {
                return [
                    'uuid' => $entry->uuid,
                    'start_date_time' => $entry->start_date_time,
                    'end_date_time' => $entry->end_date_time,
                    'work_title' => $entry->work_title,
                    'status' => $entry->status,
                    'hours_worked' => $entry->hours_worked, // Uses computed property
                ];
            });

        // This week's stats
        $thisWeekStart = Carbon::now()->startOfWeek();
        $thisWeekEntries = WorkEntry::where('user_id', $userId)
            ->where('start_date_time', '>=', $thisWeekStart)
            ->get();

        $thisWeekHours = $thisWeekEntries->sum('hours_worked');
        $thisWeekCount = $thisWeekEntries->count();

        // Create the data structure expected by the Vue component
        $employeeData = [
            'base_metrics' => [
                'total_work_logs' => $totalWorkEntries,
                'total_hours' => round($totalHoursWorked, 2),
                'average_hours_per_day' => $totalWorkEntries > 0 ? round($totalHoursWorked / max($totalWorkEntries, 1), 2) : 0,
                'completed_tasks' => WorkEntry::where('user_id', $userId)->where('status', 'completed')->count(),
                'total_hours_trend' => ['percentage' => 0, 'direction' => 'stable'],
                'daily_average_trend' => ['percentage' => 0, 'direction' => 'stable'],
                'completed_tasks_trend' => ['percentage' => 0, 'direction' => 'stable'],
                'completion_rate_trend' => ['percentage' => 0, 'direction' => 'stable'],
            ],
            'user_targets' => [
                'daily_hours_target' => 8,
                'daily_tasks_target' => 5,
                'quality_target' => 85,
            ],
            'recent_activity' => [
                'recent_entries' => $recentEntries,
                'activity_summary' => [
                    'total_entries_this_month' => $totalWorkEntries,
                    'total_hours_this_month' => round($totalHoursWorked, 2),
                    'completion_rate' => $totalWorkEntries > 0 ? round((WorkEntry::where('user_id', $userId)->where('status', 'completed')->count() / $totalWorkEntries) * 100, 1) : 0,
                    'daily_average' => $totalWorkEntries > 0 ? round($totalHoursWorked / max($totalWorkEntries, 1), 2) : 0,
                ],
            ],
            'personal_metrics' => [
                'productivity_score' => [
                    'score' => 75, // Simplified default score
                    'metrics' => [
                        'hours_per_day' => $totalWorkEntries > 0 ? round($totalHoursWorked / max($totalWorkEntries, 1), 2) : 0,
                        'task_completion_rate' => $totalWorkEntries > 0 ? round((WorkEntry::where('user_id', $userId)->where('status', 'completed')->count() / $totalWorkEntries) * 100, 1) : 0,
                        'consistency_score' => 80,
                    ],
                    'breakdown' => [
                        'total_hours' => round($totalHoursWorked, 2),
                        'completed_tasks' => WorkEntry::where('user_id', $userId)->where('status', 'completed')->count(),
                        'total_tasks' => $totalWorkEntries,
                        'work_days' => max($totalWorkEntries, 1),
                    ],
                ],
                'completion_rate' => [
                    'current_month' => [
                        'rate' => $totalWorkEntries > 0 ? round((WorkEntry::where('user_id', $userId)->where('status', 'completed')->count() / $totalWorkEntries) * 100, 1) : 0,
                        'completed' => WorkEntry::where('user_id', $userId)->where('status', 'completed')->count(),
                        'total' => $totalWorkEntries,
                        'average_completion_time' => 4.5,
                    ],
                    'weekly_trends' => [],
                    'performance_indicator' => 'good',
                ],
                'work_pattern' => [
                    'daily_patterns' => [],
                    'peak_productivity' => [
                        'morning' => ['hours' => 2.5, 'efficiency' => 85],
                        'afternoon' => ['hours' => 3.0, 'efficiency' => 90],
                        'evening' => ['hours' => 2.5, 'efficiency' => 75],
                    ],
                    'average_hours' => $totalWorkEntries > 0 ? round($totalHoursWorked / max($totalWorkEntries, 1), 2) : 0,
                    'completion_rate' => $totalWorkEntries > 0 ? round((WorkEntry::where('user_id', $userId)->where('status', 'completed')->count() / $totalWorkEntries) * 100, 1) : 0,
                ],
            ],
            'performance_insights' => [
                'trend' => [
                    'weekly_metrics' => [],
                    'trend_direction' => 'stable',
                    'trend_strength' => 0,
                    'trend_summary' => 'Your performance is stable',
                    'comparison' => [
                        'previous_period' => 0,
                        'current_period' => round($totalHoursWorked, 2),
                    ],
                ],
            ],
            // Legacy fields for backward compatibility
            'total_work_entries' => $totalWorkEntries,
            'total_hours_worked' => round($totalHoursWorked, 2),
            'recent_entries' => $recentEntries,
            'this_week_hours' => round($thisWeekHours, 2),
            'this_week_entries' => $thisWeekCount,
            'average_hours_per_entry' => $totalWorkEntries > 0 ? round($totalHoursWorked / $totalWorkEntries, 2) : 0,
        ];

        return Inertia::render('dashboard/Index', $employeeData);
    }

    /**
     * Update dashboard time filter - Simplified for now
     */
    public function updateTimeFilter(Request $request)
    {
        $validated = $request->validate([
            'time_filter' => ['required', 'string', 'in:week,month,quarter,year'],
        ]);

        $user = Auth::user();

        if ($user->hasRole('admin')) {
            // TODO: Implement organization metrics after service is fixed
            return response()->json([
                'message' => 'Organization metrics temporarily disabled',
            ]);
        }

        return response()->json([
            'message' => 'Unauthorized',
        ], 403);
    }
}
