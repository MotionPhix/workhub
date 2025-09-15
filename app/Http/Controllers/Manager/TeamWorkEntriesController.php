<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use App\Models\WorkEntry;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamWorkEntriesController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-team-work-entries');

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'min_hours' => 'nullable|numeric|min:0',
            'max_hours' => 'nullable|numeric|min:0',
            'sort_by' => 'nullable|in:date,hours,user_name,project_name',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        $query = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->with(['user:id,name,email', 'project:id,name,description']);

        // Apply filters
        if ($validated['search'] ?? null) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('project', function ($projectQuery) use ($search) {
                        $projectQuery->where('name', 'like', "%{$search}%");
                    });
            });
        }

        if ($validated['user_id'] ?? null) {
            $query->where('user_id', $validated['user_id']);
        }

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('work_date', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('work_date', '<=', $validated['end_date']);
        }

        if ($validated['min_hours'] ?? null) {
            $query->where('hours', '>=', $validated['min_hours']);
        }

        if ($validated['max_hours'] ?? null) {
            $query->where('hours', '<=', $validated['max_hours']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'date';
        $sortDirection = $validated['sort_direction'] ?? 'desc';

        switch ($sortBy) {
            case 'user_name':
                $query->join('users', 'work_entries.user_id', '=', 'users.id')
                    ->orderBy('users.name', $sortDirection)
                    ->select('work_entries.*');
                break;
            case 'project_name':
                $query->join('projects', 'work_entries.project_id', '=', 'projects.id')
                    ->orderBy('projects.name', $sortDirection)
                    ->select('work_entries.*');
                break;
            default:
                $query->orderBy($sortBy, $sortDirection);
                break;
        }

        $workEntries = $query->paginate(25);

        // Get filter options
        $teamMembers = User::where('manager_email', auth()->user()->email)
            ->select('id', 'name')
            ->orderBy('name')
            ->get();

        $projects = Project::whereHas('workEntries', function ($q) use ($teamMemberIds) {
            $q->whereIn('user_id', $teamMemberIds);
        })->select('id', 'name')->orderBy('name')->get();

        // Get summary statistics
        $stats = $this->getWorkEntriesStats($teamMemberIds, $validated);

        return Inertia::render('manager/TeamWorkEntries', [
            'workEntries' => $workEntries,
            'teamMembers' => $teamMembers,
            'projects' => $projects,
            'filters' => $validated,
            'stats' => $stats,
        ]);
    }

    public function show(Request $request, WorkEntry $workEntry)
    {
        Gate::authorize('view-work-entry-details', $workEntry);

        // Ensure this work entry belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        if (! $teamMemberIds->contains($workEntry->user_id)) {
            abort(403, 'You can only view work entries from your team members.');
        }

        $workEntry->load(['user:id,name,email', 'project:id,name,description']);

        // Get related work entries for context
        $relatedEntries = WorkEntry::where('user_id', $workEntry->user_id)
            ->where('project_id', $workEntry->project_id)
            ->where('id', '!=', $workEntry->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get();

        return Inertia::render('manager/WorkEntryDetails', [
            'workEntry' => $workEntry,
            'relatedEntries' => $relatedEntries,
        ]);
    }

    public function update(Request $request, WorkEntry $workEntry)
    {
        Gate::authorize('edit-team-work-entries');

        // Ensure this work entry belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        if (! $teamMemberIds->contains($workEntry->user_id)) {
            abort(403, 'You can only edit work entries from your team members.');
        }

        $validated = $request->validate([
            'date' => 'required|date|before_or_equal:today',
            'hours' => 'required|numeric|min:0.25|max:24',
            'description' => 'required|string|max:1000',
            'project_id' => 'required|exists:projects,id',
            'manager_notes' => 'nullable|string|max:500',
        ]);

        $workEntry->update([
            'date' => $validated['date'],
            'hours' => $validated['hours'],
            'description' => $validated['description'],
            'project_id' => $validated['project_id'],
            'manager_notes' => $validated['manager_notes'],
            'last_modified_by' => auth()->id(),
            'last_modified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Work entry updated successfully.');
    }

    public function addNote(Request $request, WorkEntry $workEntry)
    {
        Gate::authorize('add-manager-notes');

        // Ensure this work entry belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        if (! $teamMemberIds->contains($workEntry->user_id)) {
            abort(403, 'You can only add notes to work entries from your team members.');
        }

        $validated = $request->validate([
            'manager_notes' => 'required|string|max:500',
        ]);

        $workEntry->update([
            'manager_notes' => $validated['manager_notes'],
            'last_modified_by' => auth()->id(),
            'last_modified_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Manager note added successfully.');
    }

    public function approve(Request $request, WorkEntry $workEntry)
    {
        Gate::authorize('approve-work-entries');

        // Ensure this work entry belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');
        if (! $teamMemberIds->contains($workEntry->user_id)) {
            abort(403, 'You can only approve work entries from your team members.');
        }

        $workEntry->update([
            'is_approved' => true,
            'approved_by' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', 'Work entry approved successfully.');
    }

    public function bulkApprove(Request $request)
    {
        Gate::authorize('approve-work-entries');

        $validated = $request->validate([
            'work_entry_ids' => 'required|array|min:1',
            'work_entry_ids.*' => 'exists:work_entries,id',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        $workEntries = WorkEntry::whereIn('id', $validated['work_entry_ids'])
            ->whereIn('user_id', $teamMemberIds)
            ->where('is_approved', false)
            ->get();

        $approvedCount = 0;
        foreach ($workEntries as $workEntry) {
            if (Gate::allows('approve-work-entries')) {
                $workEntry->update([
                    'is_approved' => true,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                ]);
                $approvedCount++;
            }
        }

        return redirect()->back()->with('success', "Successfully approved {$approvedCount} work entries.");
    }

    public function teamProductivityAnalysis(Request $request)
    {
        Gate::authorize('view-team-productivity');

        $validated = $request->validate([
            'period' => 'nullable|in:week,month,quarter,year',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $period = $validated['period'] ?? 'month';
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        // Filter team members if specific user selected
        if ($validated['user_id'] ?? null) {
            $teamMemberIds = $teamMemberIds->filter(function ($id) use ($validated) {
                return $id === $validated['user_id'];
            });
        }

        $dateRange = $this->getDateRangeForPeriod($period);

        $query = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('work_date', [$dateRange['start'], $dateRange['end']])
            ->with(['user:id,name', 'project:id,name']);

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        $workEntries = $query->get();

        // Analyze productivity patterns
        $analysis = [
            'total_hours' => $workEntries->sum('hours'),
            'total_entries' => $workEntries->count(),
            'average_hours_per_entry' => $workEntries->count() > 0 ? round($workEntries->sum('hours') / $workEntries->count(), 2) : 0,
            'daily_breakdown' => $this->getDailyBreakdown($workEntries, $period),
            'user_breakdown' => $this->getUserBreakdown($workEntries),
            'project_breakdown' => $this->getProjectBreakdown($workEntries),
            'productivity_trends' => $this->getProductivityTrends($teamMemberIds, $period),
        ];

        $teamMembers = User::whereIn('id', $teamMemberIds)
            ->select('id', 'name')
            ->get();

        $projects = Project::whereHas('workEntries', function ($q) use ($teamMemberIds) {
            $q->whereIn('user_id', $teamMemberIds);
        })->select('id', 'name')->get();

        return Inertia::render('manager/TeamProductivityAnalysis', [
            'analysis' => $analysis,
            'period' => $period,
            'teamMembers' => $teamMembers,
            'projects' => $projects,
            'filters' => $validated,
        ]);
    }

    public function exportWorkEntries(Request $request)
    {
        Gate::authorize('export-team-work-entries');

        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'user_id' => 'nullable|exists:users,id',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id');

        $query = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->with(['user:id,name,email', 'project:id,name']);

        if ($validated['start_date'] ?? null) {
            $query->where('date', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('date', '<=', $validated['end_date']);
        }

        if ($validated['user_id'] ?? null) {
            $query->where('user_id', $validated['user_id']);
        }

        if ($validated['project_id'] ?? null) {
            $query->where('project_id', $validated['project_id']);
        }

        $workEntries = $query->orderByDesc('date')->get();

        $filename = 'team-work-entries-'.now()->format('Y-m-d');

        if ($validated['format'] === 'csv') {
            return $this->exportToCsv($workEntries, $filename);
        }

        // Excel export would require maatwebsite/excel
        return response()->json(['message' => 'Excel export requires additional package installation'], 501);
    }

    private function getWorkEntriesStats(array $teamMemberIds, array $filters): array
    {
        $query = WorkEntry::whereIn('user_id', $teamMemberIds);

        // Apply same filters as main query
        if ($filters['start_date'] ?? null) {
            $query->where('date', '>=', $filters['start_date']);
        }

        if ($filters['end_date'] ?? null) {
            $query->where('date', '<=', $filters['end_date']);
        }

        if ($filters['user_id'] ?? null) {
            $query->where('user_id', $filters['user_id']);
        }

        if ($filters['project_id'] ?? null) {
            $query->where('project_id', $filters['project_id']);
        }

        $workEntries = $query->get();

        return [
            'total_entries' => $workEntries->count(),
            'total_hours' => $workEntries->sum('hours'),
            'average_hours_per_entry' => $workEntries->count() > 0 ? round($workEntries->sum('hours') / $workEntries->count(), 2) : 0,
            'approved_entries' => $workEntries->where('is_approved', true)->count(),
            'pending_approval' => $workEntries->where('is_approved', false)->count(),
            'unique_projects' => $workEntries->pluck('project_id')->unique()->count(),
            'most_productive_day' => $this->getMostProductiveDay($workEntries),
        ];
    }

    private function getDailyBreakdown($workEntries, string $period): array
    {
        return $workEntries->groupBy(function ($entry) {
            return Carbon::parse($entry->date)->format('Y-m-d');
        })->map(function ($dayEntries) {
            return [
                'hours' => $dayEntries->sum('hours'),
                'entries' => $dayEntries->count(),
            ];
        })->sortKeys()->toArray();
    }

    private function getUserBreakdown($workEntries): array
    {
        return $workEntries->groupBy('user_id')
            ->map(function ($userEntries) {
                return [
                    'user' => $userEntries->first()->user->only(['id', 'name']),
                    'hours' => $userEntries->sum('hours'),
                    'entries' => $userEntries->count(),
                    'average_hours' => round($userEntries->sum('hours') / $userEntries->count(), 2),
                ];
            })
            ->sortByDesc('hours')
            ->values()
            ->toArray();
    }

    private function getProjectBreakdown($workEntries): array
    {
        return $workEntries->groupBy('project_id')
            ->map(function ($projectEntries) {
                return [
                    'project' => $projectEntries->first()->project->only(['id', 'name']),
                    'hours' => $projectEntries->sum('hours'),
                    'entries' => $projectEntries->count(),
                    'contributors' => $projectEntries->pluck('user_id')->unique()->count(),
                ];
            })
            ->sortByDesc('hours')
            ->values()
            ->toArray();
    }

    private function getProductivityTrends(array $teamMemberIds, string $period): array
    {
        // Get comparison data for trend analysis
        $currentRange = $this->getDateRangeForPeriod($period);
        $previousRange = $this->getDateRangeForPeriod($period, true);

        $currentData = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$currentRange['start'], $currentRange['end']])
            ->get();

        $previousData = WorkEntry::whereIn('user_id', $teamMemberIds)
            ->whereBetween('date', [$previousRange['start'], $previousRange['end']])
            ->get();

        $currentHours = $currentData->sum('hours');
        $previousHours = $previousData->sum('hours');

        $change = $previousHours > 0 ? round((($currentHours - $previousHours) / $previousHours) * 100, 1) : 0;

        return [
            'current_hours' => $currentHours,
            'previous_hours' => $previousHours,
            'percentage_change' => $change,
            'trend' => $change > 0 ? 'up' : ($change < 0 ? 'down' : 'stable'),
        ];
    }

    private function getMostProductiveDay($workEntries): array
    {
        $dailyHours = $workEntries->groupBy(function ($entry) {
            return Carbon::parse($entry->date)->format('Y-m-d');
        })->map(function ($dayEntries) {
            return $dayEntries->sum('hours');
        });

        if ($dailyHours->isEmpty()) {
            return ['date' => null, 'hours' => 0];
        }

        $maxDay = $dailyHours->keys()->first();
        $maxHours = $dailyHours->first();

        foreach ($dailyHours as $date => $hours) {
            if ($hours > $maxHours) {
                $maxHours = $hours;
                $maxDay = $date;
            }
        }

        return [
            'date' => $maxDay,
            'hours' => $maxHours,
        ];
    }

    private function getDateRangeForPeriod(string $period, bool $previous = false): array
    {
        $shift = $previous ? 1 : 0;

        return match ($period) {
            'week' => [
                'start' => now()->subWeeks($shift)->startOfWeek(),
                'end' => now()->subWeeks($shift)->endOfWeek(),
            ],
            'month' => [
                'start' => now()->subMonths($shift)->startOfMonth(),
                'end' => now()->subMonths($shift)->endOfMonth(),
            ],
            'quarter' => [
                'start' => now()->subQuarters($shift)->startOfQuarter(),
                'end' => now()->subQuarters($shift)->endOfQuarter(),
            ],
            'year' => [
                'start' => now()->subYears($shift)->startOfYear(),
                'end' => now()->subYears($shift)->endOfYear(),
            ],
            default => [
                'start' => now()->subMonths($shift)->startOfMonth(),
                'end' => now()->subMonths($shift)->endOfMonth(),
            ],
        };
    }

    private function exportToCsv($workEntries, string $filename)
    {
        return response()->streamDownload(function () use ($workEntries) {
            $output = fopen('php://output', 'w');

            // Write headers
            fputcsv($output, [
                'Date',
                'Employee',
                'Project',
                'Hours',
                'Description',
                'Approved',
                'Manager Notes',
            ]);

            // Write data rows
            foreach ($workEntries as $entry) {
                fputcsv($output, [
                    $entry->date,
                    $entry->user->name,
                    $entry->project->name,
                    $entry->hours,
                    $entry->description,
                    $entry->is_approved ? 'Yes' : 'No',
                    $entry->manager_notes ?? '',
                ]);
            }

            fclose($output);
        }, "{$filename}.csv");
    }
}
