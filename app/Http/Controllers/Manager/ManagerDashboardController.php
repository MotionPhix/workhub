<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;
use App\Models\Report;
use App\Models\User;
use App\Services\Analytics\ManagerAnalyticsService;
use App\Services\Report\ReportSchedulingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
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

        return Inertia::render('Manager/Dashboard', [
            'dashboardData' => $dashboardData,
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

        return Inertia::render('Manager/TeamReports', [
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
        $dashboardData = $this->analyticsService->getManagerDashboardData(auth()->user());

        return Inertia::render('Manager/TeamPerformance', [
            'performanceData' => $dashboardData['performance_analytics'],
            'complianceData' => $dashboardData['compliance_status'],
            'trendingData' => $dashboardData['trending_insights'],
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

        return Inertia::render('Manager/TeamSchedules', [
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
}
