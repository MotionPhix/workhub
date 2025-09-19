<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;

class TeamReportsController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('view-team-reports');

        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'status' => 'nullable|in:draft,pending,approved,rejected,sent',
            'report_type' => 'nullable|string',
            'user_id' => 'nullable|exists:users,id',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'sort_by' => 'nullable|in:created_at,updated_at,status,report_type',
            'sort_direction' => 'nullable|in:asc,desc',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();

        $query = Report::whereIn('user_id', $teamMemberIds)
            ->with(['user:id,name,email', 'department:id,name']);

        // Apply filters
        if ($validated['search'] ?? null) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    });
            });
        }

        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['report_type'] ?? null) {
            $query->where('report_type', $validated['report_type']);
        }

        if ($validated['user_id'] ?? null) {
            $query->where('user_id', $validated['user_id']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        // Apply sorting
        $sortBy = $validated['sort_by'] ?? 'created_at';
        $sortDirection = $validated['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        $reports = $query->paginate(20);

        // Get team members for filter dropdown
        $teamMembers = User::where('manager_email', auth()->user()->email)
            ->select('id', 'name', 'email')
            ->orderBy('name')
            ->get();

        // Get report statistics
        $stats = $this->getTeamReportsStats($teamMemberIds);

        return Inertia::render('manager/analytics/Reports', [
            'reports' => $reports,
            'teamMembers' => $teamMembers,
            'filters' => $validated,
            'stats' => $stats,
            'reportTypes' => $this->getAvailableReportTypes(),
        ]);
    }

    public function show(Request $request, Report $report)
    {
        Gate::authorize('view-team-report-details', $report);

        // Ensure this report belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();
        if (! in_array($report->user_id, $teamMemberIds)) {
            abort(403, 'You can only view reports from your team members.');
        }

        $report->load(['user:id,name,email', 'department:id,name', 'reportEntries']);

        // Get related reports for context
        $relatedReports = Report::where('user_id', $report->user_id)
            ->where('id', '!=', $report->id)
            ->where('report_type', $report->report_type)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get(['id', 'title', 'status', 'created_at']);

        return Inertia::render('manager/analytics/ReportDetails', [
            'report' => $report,
            'relatedReports' => $relatedReports,
        ]);
    }

    public function approve(Request $request, Report $report)
    {
        Gate::authorize('approve-report', $report);

        // Ensure this report belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();
        if (! in_array($report->user_id, $teamMemberIds)) {
            abort(403, 'You can only approve reports from your team members.');
        }

        $validated = $request->validate([
            'comments' => 'nullable|string|max:1000',
            'send_notification' => 'boolean',
        ]);

        $report->update([
            'status' => 'approved',
            'approved_by' => auth()->id(),
            'approved_at' => now(),
            'approval_comments' => $validated['comments'],
        ]);

        // Log the approval action
        $report->activities()->create([
            'user_id' => auth()->id(),
            'action' => 'approved',
            'description' => 'Report approved by manager',
            'metadata' => [
                'comments' => $validated['comments'],
                'approved_at' => now(),
            ],
        ]);

        return redirect()->back()->with('success', 'Report approved successfully.');
    }

    public function reject(Request $request, Report $report)
    {
        Gate::authorize('approve-report', $report);

        // Ensure this report belongs to a team member
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();
        if (! in_array($report->user_id, $teamMemberIds)) {
            abort(403, 'You can only reject reports from your team members.');
        }

        $validated = $request->validate([
            'reason' => 'required|string|max:1000',
            'send_notification' => 'boolean',
        ]);

        $report->update([
            'status' => 'rejected',
            'rejected_by' => auth()->id(),
            'rejected_at' => now(),
            'rejection_reason' => $validated['reason'],
        ]);

        // Log the rejection action
        $report->activities()->create([
            'user_id' => auth()->id(),
            'action' => 'rejected',
            'description' => 'Report rejected by manager',
            'metadata' => [
                'reason' => $validated['reason'],
                'rejected_at' => now(),
            ],
        ]);

        return redirect()->back()->with('success', 'Report rejected with feedback.');
    }

    public function bulkAction(Request $request)
    {
        Gate::authorize('bulk-manage-reports');

        $validated = $request->validate([
            'action' => 'required|in:approve,reject,mark_pending',
            'report_ids' => 'required|array|min:1',
            'report_ids.*' => 'exists:reports,id',
            'comments' => 'nullable|string|max:1000',
            'reason' => 'required_if:action,reject|string|max:1000',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();

        $reports = Report::whereIn('id', $validated['report_ids'])
            ->whereIn('user_id', $teamMemberIds)
            ->get();

        $successCount = 0;

        foreach ($reports as $report) {
            if (Gate::denies('approve-report', $report)) {
                continue;
            }

            switch ($validated['action']) {
                case 'approve':
                    $report->update([
                        'status' => 'approved',
                        'approved_by' => auth()->id(),
                        'approved_at' => now(),
                        'approval_comments' => $validated['comments'],
                    ]);
                    $successCount++;
                    break;

                case 'reject':
                    $report->update([
                        'status' => 'rejected',
                        'rejected_by' => auth()->id(),
                        'rejected_at' => now(),
                        'rejection_reason' => $validated['reason'],
                    ]);
                    $successCount++;
                    break;

                case 'mark_pending':
                    $report->update([
                        'status' => 'pending',
                        'approved_by' => null,
                        'approved_at' => null,
                        'rejected_by' => null,
                        'rejected_at' => null,
                    ]);
                    $successCount++;
                    break;
            }
        }

        $actionText = match ($validated['action']) {
            'approve' => 'approved',
            'reject' => 'rejected',
            'mark_pending' => 'marked as pending',
        };

        return redirect()->back()->with('success', "Successfully {$actionText} {$successCount} reports.");
    }

    public function exportReports(Request $request)
    {
        Gate::authorize('export-team-reports');

        $validated = $request->validate([
            'format' => 'required|in:csv,xlsx,json',
            'status' => 'nullable|in:draft,pending,approved,rejected,sent',
            'report_type' => 'nullable|string',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
        ]);

        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();

        $query = Report::whereIn('user_id', $teamMemberIds)
            ->with(['user:id,name,email', 'department:id,name']);

        // Apply same filters as index
        if ($validated['status'] ?? null) {
            $query->where('status', $validated['status']);
        }

        if ($validated['report_type'] ?? null) {
            $query->where('report_type', $validated['report_type']);
        }

        if ($validated['start_date'] ?? null) {
            $query->where('created_at', '>=', $validated['start_date']);
        }

        if ($validated['end_date'] ?? null) {
            $query->where('created_at', '<=', $validated['end_date']);
        }

        $reports = $query->orderByDesc('created_at')->get();

        $filename = 'team-reports-'.now()->format('Y-m-d');

        switch ($validated['format']) {
            case 'csv':
                return $this->exportToCsv($reports, $filename);
            case 'xlsx':
                return $this->exportToExcel($reports, $filename);
            case 'json':
                return response()->json($reports);
            default:
                return redirect()->back()->withErrors(['format' => 'Invalid export format.']);
        }
    }

    private function getTeamReportsStats(array $teamMemberIds): array
    {
        $totalReports = Report::whereIn('user_id', $teamMemberIds)->count();
        $pendingReports = Report::whereIn('user_id', $teamMemberIds)->where('status', 'pending')->count();
        $approvedReports = Report::whereIn('user_id', $teamMemberIds)->where('status', 'approved')->count();
        $rejectedReports = Report::whereIn('user_id', $teamMemberIds)->where('status', 'rejected')->count();

        // This month's statistics
        $thisMonthReports = Report::whereIn('user_id', $teamMemberIds)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();

        // Average approval time
        $approvalTimes = Report::whereIn('user_id', $teamMemberIds)
            ->where('status', 'approved')
            ->whereNotNull('approved_at')
            ->get()
            ->map(function ($report) {
                return $report->approved_at->diffInHours($report->created_at);
            })
            ->filter();

        $averageApprovalTime = $approvalTimes->count() > 0 ? round($approvalTimes->average(), 1) : 0;

        return [
            'total_reports' => $totalReports,
            'pending_reports' => $pendingReports,
            'approved_reports' => $approvedReports,
            'rejected_reports' => $rejectedReports,
            'this_month_reports' => $thisMonthReports,
            'average_approval_time_hours' => $averageApprovalTime,
            'approval_rate' => $totalReports > 0 ? round(($approvedReports / $totalReports) * 100, 1) : 0,
        ];
    }

    private function getAvailableReportTypes(): array
    {
        $teamMemberIds = User::where('manager_email', auth()->user()->email)->pluck('id')->toArray();

        return Report::whereIn('user_id', $teamMemberIds)
            ->select('report_type')
            ->distinct()
            ->pluck('report_type')
            ->filter()
            ->values()
            ->all();
    }

    private function exportToCsv($reports, string $filename)
    {
        return response()->streamDownload(function () use ($reports) {
            $output = fopen('php://output', 'w');

            // Write headers
            fputcsv($output, [
                'ID',
                'Title',
                'Type',
                'Status',
                'Employee',
                'Department',
                'Created At',
                'Approved At',
                'Approved By',
                'Comments',
            ]);

            // Write data rows
            foreach ($reports as $report) {
                fputcsv($output, [
                    $report->id,
                    $report->title,
                    $report->report_type,
                    $report->status,
                    $report->user->name,
                    $report->department->name ?? '',
                    $report->created_at->format('Y-m-d H:i:s'),
                    $report->approved_at?->format('Y-m-d H:i:s') ?? '',
                    $report->approvedBy->name ?? '',
                    $report->approval_comments ?? '',
                ]);
            }

            fclose($output);
        }, "{$filename}.csv");
    }

    private function exportToExcel($reports, string $filename)
    {
        // This would require maatwebsite/excel package
        return response()->json(['message' => 'Excel export requires additional package installation'], 501);
    }
}
