<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-team-members');

        $query = User::where('manager_email', auth()->user()->email)
            ->with(['department', 'workEntries', 'reports']);

        // Search functionality
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('employee_id', 'like', "%{$search}%");
            });
        }

        // Department filter
        if ($request->filled('department_id')) {
            $query->where('department_uuid', $request->department_id);
        }

        // Status filter
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
            // For 'on_leave' and other statuses, we need a proper status column
            // For now, treating them as inactive users
            elseif (in_array($request->status, ['on_leave', 'terminated'])) {
                $query->where('is_active', false);
            }
        }

        $teamMembers = $query->paginate(15);

        // Add performance metrics for each team member
        $teamMembers->getCollection()->transform(function ($member) {
            $member->performance_stats = $this->getTeamMemberStats($member);

            // Add computed status field for frontend compatibility
            $member->status = $member->is_active ? 'active' : 'inactive';

            // Add department_id for frontend compatibility (mapping department_uuid to department_id)
            $member->department_id = $member->department_uuid;

            return $member;
        });

        return Inertia::render('manager/TeamMembers', [
            'teamMembers' => $teamMembers,
            'filters' => $request->only(['search', 'department_id', 'status']),
            'departments' => \App\Models\Department::select('uuid', 'name')->get()->map(function ($dept) {
                return [
                    'id' => $dept->uuid,
                    'name' => $dept->name,
                ];
            }),
        ]);
    }

    public function show(Request $request, User $user)
    {
        Gate::authorize('view-team-member-details', $user);

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== auth()->user()->email) {
            abort(403, 'You can only view details for your team members.');
        }

        $user->load(['department', 'workEntries', 'reports']);

        // Get detailed performance stats
        $performanceStats = $this->getDetailedTeamMemberStats($user);

        // Recent activity
        $recentWorkEntries = WorkEntry::where('user_id', $user->id)
            ->orderByDesc('date')
            ->with('project')
            ->limit(10)
            ->get();

        $recentReports = Report::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        return Inertia::render('manager/TeamMemberDetails', [
            'teamMember' => $user,
            'performanceStats' => $performanceStats,
            'recentWorkEntries' => $recentWorkEntries,
            'recentReports' => $recentReports,
        ]);
    }

    public function updateTeamMember(Request $request, User $user)
    {
        Gate::authorize('manage-team-members');

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== auth()->user()->email) {
            abort(403, 'You can only manage your team members.');
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => 'sometimes|required|email|unique:users,email,'.$user->id,
            'department_id' => 'sometimes|nullable|exists:departments,uuid',
            'employee_id' => 'sometimes|nullable|string|max:50|unique:users,employee_id,'.$user->id,
            'status' => 'sometimes|required|in:active,inactive,on_leave,terminated',
            'notes' => 'sometimes|nullable|string|max:1000',
        ]);

        // Map department_id to department_uuid for database
        $updateData = $validated;
        if (isset($validated['department_id'])) {
            $updateData['department_uuid'] = $validated['department_id'];
            unset($updateData['department_id']);
        }

        // Map status to is_active for database (simplified mapping)
        if (isset($validated['status'])) {
            $updateData['is_active'] = $validated['status'] === 'active';
            // Note: You might want to store the full status in a separate column
            // or handle on_leave/terminated differently based on your business logic
            unset($updateData['status']);
        }

        $user->update($updateData);

        return redirect()->back()->with('success', 'Team member updated successfully.');
    }

    public function assignProject(Request $request, User $user)
    {
        Gate::authorize('assign-projects');

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== auth()->user()->email) {
            abort(403, 'You can only assign projects to your team members.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
            'role' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
        ]);

        // Check if user is already assigned to this project
        $existingAssignment = DB::table('project_user')
            ->where('project_id', $validated['project_id'])
            ->where('user_id', $user->id)
            ->first();

        if ($existingAssignment) {
            return redirect()->back()->withErrors(['project_id' => 'User is already assigned to this project.']);
        }

        DB::table('project_user')->insert([
            'project_id' => $validated['project_id'],
            'user_id' => $user->id,
            'role' => $validated['role'],
            'notes' => $validated['notes'],
            'assigned_at' => now(),
            'assigned_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Project assigned successfully.');
    }

    public function removeProjectAssignment(Request $request, User $user)
    {
        Gate::authorize('assign-projects');

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== auth()->user()->email) {
            abort(403, 'You can only manage assignments for your team members.');
        }

        $validated = $request->validate([
            'project_id' => 'required|exists:projects,id',
        ]);

        DB::table('project_user')
            ->where('project_id', $validated['project_id'])
            ->where('user_id', $user->id)
            ->delete();

        return redirect()->back()->with('success', 'Project assignment removed.');
    }

    public function teamPerformanceOverview(Request $request)
    {
        Gate::authorize('view-team-performance');

        $period = $request->get('period', 'current_month');

        $teamMembers = User::where('manager_email', auth()->user()->email)->get();

        $performanceData = [];
        $totalHours = 0;
        $totalReports = 0;

        foreach ($teamMembers as $member) {
            $stats = $this->getTeamMemberStats($member, $period);
            $performanceData[] = [
                'user' => $member->only(['id', 'name', 'email']),
                'stats' => $stats,
            ];

            $totalHours += $stats['total_hours'];
            $totalReports += $stats['reports_submitted'];
        }

        $teamOverview = [
            'total_members' => $teamMembers->count(),
            'total_hours' => $totalHours,
            'total_reports' => $totalReports,
            'average_hours_per_member' => $teamMembers->count() > 0 ? round($totalHours / $teamMembers->count(), 2) : 0,
        ];

        return Inertia::render('manager/TeamPerformance', [
            'performanceData' => $performanceData,
            'teamOverview' => $teamOverview,
            'period' => $period,
        ]);
    }

    private function getTeamMemberStats(User $user, string $period = 'current_month'): array
    {
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::where('user_id', $user->id)
            ->whereBetween('work_date', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        return [
            'total_hours' => $workEntries->sum('hours'),
            'total_entries' => $workEntries->count(),
            'reports_submitted' => $reports->count(),
            'reports_approved' => $reports->where('status', 'approved')->count(),
            'reports_pending' => $reports->where('status', 'pending')->count(),
            'reports_rejected' => $reports->where('status', 'rejected')->count(),
            'average_hours_per_day' => $workEntries->count() > 0 ? round($workEntries->sum('hours') / $workEntries->count(), 2) : 0,
            'last_activity' => $workEntries->max('date') ?? $reports->max('created_at'),
        ];
    }

    private function getDetailedTeamMemberStats(User $user): array
    {
        $basicStats = $this->getTeamMemberStats($user);

        // Additional detailed statistics
        $workEntries = WorkEntry::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(90))
            ->get();

        $projectStats = $workEntries->groupBy('project_id')
            ->map(function ($entries) {
                return [
                    'total_hours' => $entries->sum('hours'),
                    'entries_count' => $entries->count(),
                ];
            });

        // Weekly performance trend
        $weeklyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $weekEntries = $workEntries->filter(function ($entry) use ($weekStart, $weekEnd) {
                return $entry->date >= $weekStart && $entry->date <= $weekEnd;
            });

            $weeklyStats[] = [
                'week' => $weekStart->format('M d'),
                'hours' => $weekEntries->sum('hours'),
                'entries' => $weekEntries->count(),
            ];
        }

        return array_merge($basicStats, [
            'project_distribution' => $projectStats,
            'weekly_trend' => $weeklyStats,
            'productivity_score' => $this->calculateProductivityScore($user),
        ]);
    }

    private function calculateProductivityScore(User $user): float
    {
        // Simple productivity score based on consistency and output
        $recentEntries = WorkEntry::where('user_id', $user->id)
            ->where('date', '>=', now()->subDays(30))
            ->get();

        if ($recentEntries->isEmpty()) {
            return 0;
        }

        $totalHours = $recentEntries->sum('hours');
        $workingDays = $recentEntries->pluck('date')->unique()->count();
        $averageHoursPerDay = $workingDays > 0 ? $totalHours / $workingDays : 0;

        // Score based on consistency (working days) and productivity (hours)
        $consistencyScore = min($workingDays / 22, 1) * 50; // Max 50 points for consistency
        $productivityScore = min($averageHoursPerDay / 8, 1) * 50; // Max 50 points for productivity

        return round($consistencyScore + $productivityScore, 1);
    }

    private function getDateRangeForPeriod(string $period): array
    {
        return match ($period) {
            'current_week' => [
                'start' => now()->startOfWeek(),
                'end' => now()->endOfWeek(),
            ],
            'last_week' => [
                'start' => now()->subWeek()->startOfWeek(),
                'end' => now()->subWeek()->endOfWeek(),
            ],
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
            default => [
                'start' => now()->startOfMonth(),
                'end' => now()->endOfMonth(),
            ],
        };
    }
}
