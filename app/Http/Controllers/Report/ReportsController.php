<?php

namespace App\Http\Controllers\Report;

use App\Http\Controllers\Controller;
use App\Models\WorkEntry;
use App\Services\AdvancedProductivityInsightService;
use App\Services\Report\ReportGenerationService;
use App\Services\Report\ReportExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class ReportsController extends Controller
{
  protected $insightService;
  protected $reportService;
  protected $exportService;

  public function __construct(
    AdvancedProductivityInsightService $insightService,
    ReportGenerationService $reportService,
    ReportExportService $exportService
  ) {
    $this->insightService = $insightService;
    $this->reportService = $reportService;
    $this->exportService = $exportService;
  }

  public function index(Request $request)
  {
    $filters = $this->validateFilters($request);
    $cacheKey = $this->generateCacheKey($filters);

    // Get the base query results first
    $query = $this->buildQuery($filters);
    $reports = $query->latest('work_date')->paginate(15);

    // Cache only the stats data
    $stats = Cache::remember($cacheKey . '_stats', 3600, function () use ($filters) {
      $statsQuery = $this->buildQuery($filters);
      return [
        'total_hours' => $statsQuery->sum('hours_worked'),
        'average_hours_per_day' => $statsQuery->avg('hours_worked'),
        'departments' => $this->reportService->getDepartmentStats(),
        'productivity_trends' => $this->reportService->getProductivityTrends([
          Carbon::parse($filters['start_date'] ?? '-30 days'),
          Carbon::parse($filters['end_date'] ?? 'now')
        ]),
        'top_performers' => $this->reportService->getTopPerformers($filters),
        'burnout_risks' => $this->insightService->getTeamBurnoutRisks($filters),
        'focus_time_analytics' => $this->insightService->getTeamFocusTimeAnalytics($filters)
      ];
    });

    $this->enhanceReportsWithInsights($reports);

    return Inertia::render('Reports/Index', [
      'reports' => $reports,
      'stats' => $stats,
      'filters' => $filters,
      'defaultDateRange' => [
        'start' => Carbon::now()->subDays(30)->format('Y-m-d'),
        'end' => Carbon::now()->format('Y-m-d')
      ]
    ]);
  }

  protected function buildQuery(array $filters)
  {
    $query = WorkEntry::query()
      ->select('work_entries.*')
      ->join('users', 'work_entries.user_id', '=', 'users.id')
      ->join('departments', 'users.department_uuid', '=', 'departments.uuid')
      ->with([
        'user' => function ($query) {
          $query->select('id', 'name', 'email', 'department_uuid');
        },
        'user.department' => function ($query) {
          $query->select('uuid', 'name');
        },
        'tags'
      ]);

    if (!empty($filters['start_date'])) {
      $query->where('work_entries.work_date', '>=', $filters['start_date']);
    }

    if (!empty($filters['end_date'])) {
      $query->where('work_entries.work_date', '<=', $filters['end_date']);
    }

    if (!empty($filters['department'])) {
      $query->where('departments.uuid', $filters['department']);
    }

    if (!empty($filters['status'])) {
      $query->where('work_entries.status', $filters['status']);
    }

    // Default to last 30 days if no date range is specified
    if (empty($filters['start_date']) && empty($filters['end_date'])) {
      $query->where('work_entries.work_date', '>=', Carbon::now()->subDays(30));
    }

    return $query;
  }

  protected function enhanceReportsWithInsights($reports)
  {
    $reports->through(function ($report) {
      try {
        $insights = [
          'productivity_score' => $report->productivity_score ?? 0,
          'focus_time_analysis' => [
            'total_focus_hours' => $report->focus_time ?? 0,
            'average_focus_session' => $report->average_session_length ?? 0,
          ],
          'burnout_risk' => [
            'risk_level' => $this->getBurnoutRiskLevel($report->burnout_risk_score),
            'score' => $report->burnout_risk_score ?? 0,
          ],
          'energy_patterns' => [
            'energy_level' => $report->energy_level ?? 0,
            'activities' => $report->activities ?? [],
          ],
        ];

        // Merge with any additional real-time insights
        $realtimeInsights = $this->insightService->generateComprehensiveInsights($report->user_id);
        return array_merge($report->toArray(), ['insights' => array_merge($insights, $realtimeInsights)]);
      } catch (\Exception $e) {
        Log::error('Failed to generate insights for report', [
          'report_id' => $report->id,
          'error' => $e->getMessage()
        ]);
        return array_merge($report->toArray(), ['insights' => null]);
      }
    });
  }

  protected function getBurnoutRiskLevel(?float $score): string
  {
    if ($score === null) {
      return 'Not Available';
    }

    return match (true) {
      $score >= 0.7 => 'High',
      $score >= 0.4 => 'Moderate',
      default => 'Low'
    };
  }

  public function show(WorkEntry $report)
  {
    $report->load(['user', 'tags']);
    $insights = $this->insightService->generateComprehensiveInsights($report->user_id);

    return Inertia::render('Reports/Show', [
      'report' => $report,
      'insights' => $insights,
      'trends' => $this->reportService->generateProductivityTrends($report)
    ]);
  }

  public function export(Request $request, string $format = 'pdf')
  {
    $filters = $this->validateFilters($request);
    $data = $this->reportService->generateReportData($filters);
    return $this->exportService->export($data, $format);
  }

  protected function validateFilters(Request $request)
  {
    return $request->validate([
      'start_date' => 'nullable|date',
      'end_date' => 'nullable|date|after_or_equal:start_date',
      'department' => 'nullable|string|exists:departments,uuid',
      'status' => 'nullable|string|in:pending,completed,failed',
      'tag' => 'nullable|string'
    ]);
  }

  protected function generateCacheKey(array $filters): string
  {
    ksort($filters);
    return 'work_entries.reports.' . md5(serialize($filters));
  }
}
