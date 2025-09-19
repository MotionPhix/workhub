<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Models\UserInvite;
use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-team-members');

        $query = User::where('manager_email', Auth::user()->email)
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

        // Get team stats for the dashboard
        $teamStats = $this->getTeamStats();

        return Inertia::render('manager/team/Index', [
            'teamMembers' => $teamMembers,
            'filters' => $request->only(['search', 'department_id', 'status']),
            'departments' => \App\Models\Department::select('uuid', 'name')->get()->map(function ($dept) {
                return [
                    'id' => $dept->uuid,
                    'name' => $dept->name,
                ];
            }),
            'teamStats' => $teamStats,
        ]);
    }

    public function show(Request $request, User $user)
    {
        Gate::authorize('view-team-member-details', $user);

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== Auth::user()->email) {
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
        if ($user->manager_email !== Auth::user()->email) {
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
        if ($user->manager_email !== Auth::user()->email) {
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
            'assigned_by' => Auth::id(),
        ]);

        return redirect()->back()->with('success', 'Project assigned successfully.');
    }

    public function removeProjectAssignment(Request $request, User $user)
    {
        Gate::authorize('assign-projects');

        // Ensure this user is part of the manager's team
        if ($user->manager_email !== Auth::user()->email) {
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

        $teamMembers = User::where('manager_email', Auth::user()->email)->get();

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

        return Inertia::render('manager/team/Performance', [
            'performanceData' => $performanceData,
            'teamOverview' => $teamOverview,
            'period' => $period,
        ]);
    }

    public function invitations(Request $request)
    {
        Gate::authorize('create-invitations');

        $filters = $request->validate([
            'status' => 'nullable|in:pending,accepted,declined,expired',
            'search' => 'nullable|string|max:255',
        ]);

        // Get invitations sent by the current manager
        $query = \App\Models\UserInvite::where('invited_by', auth()->id())
            ->with(['inviter', 'department'])
            ->orderByDesc('invited_at');

        // Apply filters
        if (!empty($filters['status'])) {
            if ($filters['status'] === 'expired') {
                $query->expired();
            } else {
                $query->where('status', $filters['status']);
            }
        }

        if (!empty($filters['search'])) {
            $query->where(function ($q) use ($filters) {
                $q->where('email', 'like', "%{$filters['search']}%")
                    ->orWhere('name', 'like', "%{$filters['search']}%");
            });
        }

        $invitations = $query->paginate(15);

        return Inertia::render('manager/team/Invitations', [
            'invitations' => $invitations,
            'filters' => $filters,
        ]);
    }

    public function createInvite()
    {
        Gate::authorize('create-invitations');

        // Get roles that managers can invite
        $allowedRoles = ['employee']; // Managers can only invite employees
        if (auth()->user()->hasRole('admin')) {
            $allowedRoles = ['employee', 'manager']; // Admins can invite employees and managers
        }

        return Inertia::render('shared/InviteUser', [
            'departments' => \App\Models\Department::select('uuid as id', 'name')->get(),
            'roles' => \Spatie\Permission\Models\Role::whereIn('name', $allowedRoles)
                ->select('name as id', 'name as name')
                ->get()
                ->map(function ($role) {
                    return [
                        'id' => $role->name, // Ensure id is the role name string
                        'name' => $role->name
                    ];
                }),
            'managers' => \App\Models\User::role(['manager', 'admin'])
                ->select('id', 'name', 'email')
                ->get(),
            'currentUser' => auth()->user()->only(['id', 'name', 'email']),
            'isManager' => auth()->user()->hasRole('manager'),
            'isAdmin' => auth()->user()->hasRole('admin'),
        ]);
    }

    public function invite(Request $request)
    {
        Gate::authorize('create-invitations');

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'required',
                'email',
                'max:255',
                'unique:users,email',
                'unique:user_invites,email,NULL,id,status,pending',
            ],
            'department_uuid' => 'required|exists:departments,uuid',
            'role_name' => 'required|string|exists:roles,name',
            'manager_email' => [
                'nullable',
                'email',
                'exists:users,email',
                'different:email',
            ],
            'expires_in_days' => 'nullable|integer|min:1|max:30',
            'welcome_message' => 'nullable|string|max:500',
        ]);

        // Ensure managers can only invite employees and assign themselves as manager
        if (Auth::user()->hasRole('manager')) {
            if ($validated['role_name'] !== 'employee') {
                return redirect()->back()
                    ->withErrors(['role_name' => 'Managers can only invite employees.'])
                    ->withInput();
            }
            $validated['manager_email'] = Auth::user()->email;
        }

        try {
            $invitationData = [
                'name' => $validated['name'],
                'email' => $validated['email'],
                'department_uuid' => $validated['department_uuid'] ?? null,
                'manager_email' => $validated['manager_email'] ?? null,
                'role_name' => $validated['role_name'],
                'expires_in_days' => $validated['expires_in_days'] ?? 7,
                'welcome_message' => $validated['welcome_message'] ?? null,
            ];

            $invitationService = app(\App\Services\Auth\InvitationService::class);
            $invitation = $invitationService->sendInvitation($invitationData, Auth::user());

            return back()->with('success', "Invitation sent successfully to {$invitation->email}");

        } catch (\Exception $e) {
            return back()
                ->withErrors(['email' => $e->getMessage()])
                ->withInput();
        }
    }

    public function resendInvitation(UserInvite $invitation)
    {
        Gate::authorize('resend-invitations');

        // Ensure this invitation is for a team member of this manager
        if ($invitation->manager_email !== Auth::user()->email) {
            abort(403, 'You can only manage invitations for your team members.');
        }

        try {
            $invitationService = app(\App\Services\Auth\InvitationService::class);

            if (!$invitationService->sendReminder($invitation)) {
                return redirect()->back()
                    ->withErrors(['message' => 'Cannot resend this invitation.']);
            }

            return redirect()->back()->with('success', 'Invitation resent successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function cancelInvitation(UserInvite $invitation)
    {
        Gate::authorize('cancel-invitations');

        // Ensure this invitation is for a team member of this manager
        if ($invitation->manager_email !== Auth::user()->email) {
            abort(403, 'You can only manage invitations for your team members.');
        }

        try {
            $invitationService = app(\App\Services\Auth\InvitationService::class);

            if (!$invitationService->cancelInvitation($invitation, Auth::user())) {
                return redirect()->back()
                    ->withErrors(['message' => 'Cannot cancel this invitation.']);
            }

            return redirect()->back()->with('success', 'Invitation cancelled successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function update(Request $request, User $user)
    {
        return $this->updateTeamMember($request, $user);
    }

    public function export(Request $request)
    {
        Gate::authorize('export-team-data');

        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx,json',
            'status' => 'nullable|in:active,inactive,on_leave,terminated',
            'department_id' => 'nullable|exists:departments,uuid',
        ]);

        $query = User::where('manager_email', Auth::user()->email)
            ->with(['department']);

        // Apply filters
        if ($request->filled('status')) {
            if ($request->status === 'active') {
                $query->where('is_active', true);
            } elseif ($request->status === 'inactive') {
                $query->where('is_active', false);
            }
        }

        if ($request->filled('department_id')) {
            $query->where('department_uuid', $request->department_id);
        }

        $teamMembers = $query->get();

        $filename = 'team-members-'.now()->format('Y-m-d');

        switch ($validated['format']) {
            case 'csv':
                return $this->exportToCsv($teamMembers, $filename);
            case 'xlsx':
                return $this->exportToExcel($teamMembers, $filename);
            case 'json':
                return response()->json($teamMembers);
            default:
                return redirect()->back()->withErrors(['format' => 'Invalid export format.']);
        }
    }

    private function exportToCsv($teamMembers, string $filename)
    {
        return response()->streamDownload(function () use ($teamMembers) {
            $output = fopen('php://output', 'w');

            // Write headers
            fputcsv($output, [
                'ID',
                'Name',
                'Email',
                'Employee ID',
                'Department',
                'Status',
                'Last Activity',
                'Total Hours (This Month)',
                'Reports Submitted (This Month)',
            ]);

            // Write data rows
            foreach ($teamMembers as $member) {
                $stats = $this->getTeamMemberStats($member);

                fputcsv($output, [
                    $member->uuid,
                    $member->name,
                    $member->email,
                    $member->employee_id ?? '',
                    $member->department->name ?? '',
                    $member->is_active ? 'Active' : 'Inactive',
                    $stats['last_activity'] ? \Carbon\Carbon::parse($stats['last_activity'])->format('Y-m-d H:i:s') : '',
                    $stats['total_hours'] ?? 0,
                    $stats['reports_submitted'] ?? 0,
                ]);
            }

            fclose($output);
        }, "{$filename}.csv");
    }

    private function exportToExcel($teamMembers, string $filename)
    {
        // This would require maatwebsite/excel package
        return response()->json(['message' => 'Excel export requires additional package installation'], 501);
    }

    private function getTeamStats(): array
    {
        $teamMembers = User::where('manager_email', Auth::user()->email)->get();

        $activeMembers = $teamMembers->where('is_active', true)->count();
        $totalMembers = $teamMembers->count();
        $inactiveMembers = $teamMembers->where('is_active', false)->count();

        // Current month data
        $currentMonthStart = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();

        $currentWorkEntries = WorkEntry::whereIn('user_id', $teamMembers->pluck('id'))
            ->whereBetween('start_date_time', [$currentMonthStart, $currentMonthEnd])
            ->get();

        $currentTotalWorkEntries = $currentWorkEntries->count();
        $currentTotalHours = $currentWorkEntries->sum('hours_worked');
        $currentAvgHoursPerMember = $totalMembers > 0 ? round($currentTotalHours / $totalMembers, 1) : 0;

        // Previous month data for comparison
        $previousMonthStart = now()->subMonth()->startOfMonth();
        $previousMonthEnd = now()->subMonth()->endOfMonth();

        $previousWorkEntries = WorkEntry::whereIn('user_id', $teamMembers->pluck('id'))
            ->whereBetween('start_date_time', [$previousMonthStart, $previousMonthEnd])
            ->get();

        $previousTotalWorkEntries = $previousWorkEntries->count();
        $previousTotalHours = $previousWorkEntries->sum('hours_worked');
        $previousAvgHoursPerMember = $totalMembers > 0 ? round($previousTotalHours / $totalMembers, 1) : 0;

        // Get team member count from last month for comparison
        $previousTotalMembers = User::where('manager_email', Auth::user()->email)
            ->where('created_at', '<=', $previousMonthEnd)
            ->count();

        // Get pending reports
        $pendingReports = Report::whereIn('user_id', $teamMembers->pluck('id'))
            ->where('status', 'pending')
            ->count();

        // Calculate changes
        $memberChange = $this->calculateChange($totalMembers, $previousTotalMembers);
        $avgHoursChange = $this->calculatePercentageChange($currentAvgHoursPerMember, $previousAvgHoursPerMember);
        $workEntriesChange = $this->calculatePercentageChange($currentTotalWorkEntries, $previousTotalWorkEntries);

        return [
            'total_members' => $totalMembers,
            'active_members' => $activeMembers,
            'on_leave' => 0, // This would need a proper status field
            'inactive_members' => $inactiveMembers,
            'avg_hours_per_member' => $currentAvgHoursPerMember,
            'total_work_entries' => $currentTotalWorkEntries,
            'pending_reports' => $pendingReports,
            // Add change calculations
            'member_change' => $memberChange,
            'avg_hours_change' => $avgHoursChange,
            'work_entries_change' => $workEntriesChange,
            'pending_status' => $pendingReports > 0 ? 'Urgent' : 'None',
        ];
    }

    private function calculateChange(int $current, int $previous): string
    {
        $diff = $current - $previous;
        if ($diff > 0) {
            return "+{$diff} this month";
        } elseif ($diff < 0) {
            return "{$diff} this month";
        }
        return "No change";
    }

    private function calculatePercentageChange(float $current, float $previous): string
    {
        if ($previous == 0) {
            return $current > 0 ? "+100%" : "No data";
        }

        $percentChange = round((($current - $previous) / $previous) * 100, 1);

        if ($percentChange > 0) {
            return "+{$percentChange}%";
        } elseif ($percentChange < 0) {
            return "{$percentChange}%";
        }
        return "No change";
    }

    private function getTeamMemberStats(User $user, string $period = 'current_month'): array
    {
        $dateRange = $this->getDateRangeForPeriod($period);

        $workEntries = WorkEntry::where('user_id', $user->id)
            ->whereBetween('start_date_time', [$dateRange['start'], $dateRange['end']])
            ->get();

        $reports = Report::where('user_id', $user->id)
            ->whereBetween('created_at', [$dateRange['start'], $dateRange['end']])
            ->get();

        return [
            'total_hours' => $workEntries->sum('hours_worked'),
            'total_entries' => $workEntries->count(),
            'reports_submitted' => $reports->count(),
            'reports_approved' => $reports->where('status', 'approved')->count(),
            'reports_pending' => $reports->where('status', 'pending')->count(),
            'reports_rejected' => $reports->where('status', 'rejected')->count(),
            'average_hours_per_day' => $workEntries->count() > 0 ? round($workEntries->sum('hours_worked') / $workEntries->count(), 2) : 0,
            'last_activity' => $workEntries->max('start_date_time') ?? $reports->max('created_at'),
            'efficiency_score' => $this->calculateEfficiencyScore($workEntries),
        ];
    }

    private function getDetailedTeamMemberStats(User $user): array
    {
        $basicStats = $this->getTeamMemberStats($user);

        // Additional detailed statistics
        $workEntries = WorkEntry::where('user_id', $user->id)
            ->where('start_date_time', '>=', now()->subDays(90))
            ->get();

        $projectStats = $workEntries->groupBy('project_uuid')
            ->map(function ($entries) {
                return [
                    'total_hours' => $entries->sum('hours_worked'),
                    'entries_count' => $entries->count(),
                ];
            });

        // Weekly performance trend
        $weeklyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $weekStart = now()->subWeeks($i)->startOfWeek();
            $weekEnd = now()->subWeeks($i)->endOfWeek();

            $weekEntries = $workEntries->filter(function ($entry) use ($weekStart, $weekEnd) {
                return $entry->start_date_time >= $weekStart && $entry->start_date_time <= $weekEnd;
            });

            $weeklyStats[] = [
                'week' => $weekStart->format('M d'),
                'hours' => $weekEntries->sum('hours_worked'),
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
            ->where('start_date_time', '>=', now()->subDays(30))
            ->get();

        if ($recentEntries->isEmpty()) {
            return 0;
        }

        $totalHours = $recentEntries->sum('hours_worked');
        $workingDays = $recentEntries->pluck('start_date_time')->map(function($date) {
            return \Carbon\Carbon::parse($date)->format('Y-m-d');
        })->unique()->count();
        $averageHoursPerDay = $workingDays > 0 ? $totalHours / $workingDays : 0;

        // Score based on consistency (working days) and productivity (hours)
        $consistencyScore = min($workingDays / 22, 1) * 50; // Max 50 points for consistency
        $productivityScore = min($averageHoursPerDay / 8, 1) * 50; // Max 50 points for productivity

        return round($consistencyScore + $productivityScore, 1);
    }

    private function calculateEfficiencyScore($workEntries): float
    {
        if ($workEntries->isEmpty()) {
            return 0;
        }

        // Calculate efficiency based on completed vs total entries
        $completedEntries = $workEntries->where('status', 'completed')->count();
        $totalEntries = $workEntries->count();

        return $totalEntries > 0 ? round(($completedEntries / $totalEntries) * 100, 1) : 0;
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
