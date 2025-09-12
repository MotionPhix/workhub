<?php

namespace App\Services\Report;

use App\Enums\ReportType;
use App\Models\Report;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ReportValidationService
{
    private array $validationRules = [
        'sales' => [
            'leads_generated' => 'required|integer|min:0|max:10000',
            'calls_made' => 'required|integer|min:0|max:1000',
            'meetings_scheduled' => 'required|integer|min:0|max:500',
            'deals_closed' => 'required|integer|min:0|max:100',
            'revenue_generated' => 'required|numeric|min:0|max:10000000',
            'conversion_rate' => 'required|numeric|min:0|max:100',
            'pipeline_value' => 'required|numeric|min:0|max:50000000',
        ],
        'marketing' => [
            'campaigns_launched' => 'required|integer|min:0|max:100',
            'leads_generated' => 'required|integer|min:0|max:10000',
            'content_pieces_created' => 'required|integer|min:0|max:500',
            'social_media_engagement' => 'required|numeric|min:0|max:100',
            'website_traffic' => 'required|integer|min:0|max:1000000',
            'email_open_rates' => 'required|numeric|min:0|max:100',
            'ad_spend' => 'required|numeric|min:0|max:500000',
            'roi_metrics' => 'required|numeric|min:-100|max:1000',
        ],
        'design' => [
            'designs_completed' => 'required|integer|min:0|max:200',
            'revisions_made' => 'required|integer|min:0|max:500',
            'client_feedback_score' => 'required|numeric|min:1|max:10',
            'time_per_project' => 'required|numeric|min:0.1|max:200',
            'creative_concepts' => 'required|integer|min:0|max:100',
            'brand_guidelines_followed' => 'required|numeric|min:0|max:100',
            'software_used' => 'required|string|max:500',
        ],
        'bids_tenders' => [
            'bids_submitted' => 'required|integer|min:0|max:100',
            'tenders_won' => 'required|integer|min:0|max:50',
            'proposal_value' => 'required|numeric|min:0|max:100000000',
            'success_rate' => 'required|numeric|min:0|max:100',
            'client_meetings' => 'required|integer|min:0|max:200',
            'documentation_prepared' => 'required|integer|min:0|max:500',
            'compliance_checks' => 'required|integer|min:0|max:100',
        ],
        'development' => [
            'features_completed' => 'required|integer|min:0|max:100',
            'bugs_fixed' => 'required|integer|min:0|max:500',
            'code_reviews_done' => 'required|integer|min:0|max:200',
            'tests_written' => 'required|integer|min:0|max:1000',
            'deployment_frequency' => 'required|integer|min:0|max:100',
            'performance_improvements' => 'required|integer|min:0|max:50',
            'technical_debt_addressed' => 'required|integer|min:0|max:100',
        ],
        'general' => [
            'tasks_completed' => 'required|integer|min:0|max:500',
            'hours_worked' => 'required|numeric|min:0|max:200',
            'meetings_attended' => 'required|integer|min:0|max:100',
            'goals_achieved' => 'required|integer|min:0|max:50',
        ],
    ];

    public function validateReportData(array $data, ReportType $reportType): array
    {
        $rules = $this->getValidationRules($reportType);

        // Add common validation rules
        $rules = array_merge($rules, [
            'title' => 'required|string|max:255',
            'period_start' => 'required|date|before_or_equal:period_end',
            'period_end' => 'required|date|after_or_equal:period_start',
            'content' => 'nullable|string|max:10000',
        ]);

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizeData($validator->validated(), $reportType);
    }

    public function validateReportEntry(array $data): array
    {
        $rules = [
            'title' => 'required|string|max:255',
            'description' => 'required|string|max:2000',
            'entry_date' => 'required|date|before_or_equal:today',
            'hours_worked' => 'required|numeric|min:0.1|max:24',
            'priority' => 'required|in:low,medium,high',
            'completion_status' => 'required|in:pending,in_progress,completed',
            'tags' => 'nullable|array|max:10',
            'tags.*' => 'string|max:50',
            'metrics' => 'nullable|array',
        ];

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $this->sanitizeEntryData($validator->validated());
    }

    private function getValidationRules(ReportType $reportType): array
    {
        return $this->validationRules[$reportType->value] ?? $this->validationRules['general'];
    }

    private function sanitizeData(array $data, ReportType $reportType): array
    {
        // Sanitize text fields
        if (isset($data['title'])) {
            $data['title'] = strip_tags(trim($data['title']));
        }

        if (isset($data['content'])) {
            $data['content'] = clean($data['content']); // Uses Mews Purifier
        }

        // Apply business logic validation
        $data = $this->applyBusinessRules($data, $reportType);

        return $data;
    }

    private function sanitizeEntryData(array $data): array
    {
        if (isset($data['title'])) {
            $data['title'] = strip_tags(trim($data['title']));
        }

        if (isset($data['description'])) {
            $data['description'] = clean($data['description']);
        }

        if (isset($data['tags'])) {
            $data['tags'] = array_map('trim', array_filter($data['tags']));
        }

        return $data;
    }

    private function applyBusinessRules(array $data, ReportType $reportType): array
    {
        switch ($reportType) {
            case ReportType::SALES:
                $data = $this->validateSalesBusinessRules($data);
                break;
            case ReportType::MARKETING:
                $data = $this->validateMarketingBusinessRules($data);
                break;
            case ReportType::DESIGN:
                $data = $this->validateDesignBusinessRules($data);
                break;
                // Add other types as needed
        }

        return $data;
    }

    private function validateSalesBusinessRules(array $data): array
    {
        // Conversion rate should match calculated rate
        if (isset($data['leads_generated'], $data['deals_closed']) && $data['leads_generated'] > 0) {
            $calculatedRate = ($data['deals_closed'] / $data['leads_generated']) * 100;
            $providedRate = $data['conversion_rate'] ?? 0;

            if (abs($calculatedRate - $providedRate) > 5) { // Allow 5% tolerance
                Log::warning('Sales conversion rate mismatch', [
                    'calculated' => $calculatedRate,
                    'provided' => $providedRate,
                    'leads' => $data['leads_generated'],
                    'deals' => $data['deals_closed'],
                ]);
            }
        }

        // Revenue should be reasonable based on deals closed
        if (isset($data['deals_closed'], $data['revenue_generated'])) {
            $avgDealValue = $data['deals_closed'] > 0
                ? $data['revenue_generated'] / $data['deals_closed']
                : 0;

            if ($avgDealValue > 1000000) { // Flag deals > $1M average
                Log::info('High-value sales report detected', [
                    'deals_closed' => $data['deals_closed'],
                    'revenue' => $data['revenue_generated'],
                    'avg_deal_value' => $avgDealValue,
                ]);
            }
        }

        return $data;
    }

    private function validateMarketingBusinessRules(array $data): array
    {
        // ROI should be reasonable based on ad spend
        if (isset($data['ad_spend'], $data['roi_metrics']) && $data['ad_spend'] > 0) {
            if ($data['roi_metrics'] > 500) { // ROI > 500% is suspicious
                Log::warning('Unusually high marketing ROI reported', [
                    'roi' => $data['roi_metrics'],
                    'ad_spend' => $data['ad_spend'],
                ]);
            }
        }

        // Email open rates should be realistic
        if (isset($data['email_open_rates']) && $data['email_open_rates'] > 80) {
            Log::info('High email open rate reported', [
                'open_rate' => $data['email_open_rates'],
            ]);
        }

        return $data;
    }

    private function validateDesignBusinessRules(array $data): array
    {
        // Client feedback should be reasonable for revisions made
        if (isset($data['revisions_made'], $data['client_feedback_score'])) {
            if ($data['revisions_made'] > 10 && $data['client_feedback_score'] > 8) {
                Log::info('High revisions with high satisfaction reported', [
                    'revisions' => $data['revisions_made'],
                    'feedback_score' => $data['client_feedback_score'],
                ]);
            }
        }

        return $data;
    }

    public function performDataIntegrityCheck(Report $report): array
    {
        $issues = [];

        // Check for missing required metrics
        $requiredFields = $report->getRequiredMetrics();
        $providedFields = array_keys($report->metrics_data ?? []);
        $missingFields = array_diff($requiredFields, $providedFields);

        if (! empty($missingFields)) {
            $issues[] = [
                'type' => 'missing_data',
                'severity' => 'warning',
                'message' => 'Missing required metrics: '.implode(', ', $missingFields),
                'fields' => $missingFields,
            ];
        }

        // Check for suspicious data patterns
        $suspiciousPatterns = $this->detectSuspiciousPatterns($report);
        $issues = array_merge($issues, $suspiciousPatterns);

        // Check date consistency
        $dateIssues = $this->validateDateConsistency($report);
        $issues = array_merge($issues, $dateIssues);

        return $issues;
    }

    private function detectSuspiciousPatterns(Report $report): array
    {
        $issues = [];
        $metrics = $report->metrics_data ?? [];

        // Check for all zeros or all same values
        $nonZeroMetrics = array_filter($metrics, fn ($value) => is_numeric($value) && $value != 0);
        if (empty($nonZeroMetrics) && ! empty($metrics)) {
            $issues[] = [
                'type' => 'suspicious_pattern',
                'severity' => 'warning',
                'message' => 'All metrics are zero or empty',
            ];
        }

        // Check for identical values across different metrics
        $numericValues = array_filter($metrics, 'is_numeric');
        $valueCounts = array_count_values($numericValues);
        foreach ($valueCounts as $value => $count) {
            if ($count >= 3 && $value != 0) { // Same non-zero value appears 3+ times
                $issues[] = [
                    'type' => 'suspicious_pattern',
                    'severity' => 'info',
                    'message' => "Value {$value} appears {$count} times across different metrics",
                ];
            }
        }

        return $issues;
    }

    private function validateDateConsistency(Report $report): array
    {
        $issues = [];

        // Check if report period is too far in the future
        if ($report->period_end->isFuture() && $report->period_end->gt(now()->addDays(7))) {
            $issues[] = [
                'type' => 'date_inconsistency',
                'severity' => 'warning',
                'message' => 'Report period extends far into the future',
            ];
        }

        // Check if report period is too long
        $periodLength = $report->period_start->diffInDays($report->period_end);
        if ($periodLength > 90) { // More than 3 months
            $issues[] = [
                'type' => 'date_inconsistency',
                'severity' => 'info',
                'message' => "Report period is {$periodLength} days long, which is unusually long",
            ];
        }

        return $issues;
    }

    public function getValidationSummary(Report $report): array
    {
        $completionPercentage = $report->calculateCompletionPercentage();
        $integrityIssues = $this->performDataIntegrityCheck($report);

        $severityCounts = array_count_values(array_column($integrityIssues, 'severity'));

        return [
            'completion_percentage' => $completionPercentage,
            'is_complete' => $report->isComplete(),
            'integrity_issues' => $integrityIssues,
            'issue_summary' => [
                'total_issues' => count($integrityIssues),
                'critical' => $severityCounts['critical'] ?? 0,
                'warning' => $severityCounts['warning'] ?? 0,
                'info' => $severityCounts['info'] ?? 0,
            ],
            'validation_status' => $this->getOverallValidationStatus($completionPercentage, $integrityIssues),
        ];
    }

    private function getOverallValidationStatus(int $completionPercentage, array $issues): string
    {
        $criticalIssues = array_filter($issues, fn ($issue) => $issue['severity'] === 'critical');
        $warningIssues = array_filter($issues, fn ($issue) => $issue['severity'] === 'warning');

        if (! empty($criticalIssues)) {
            return 'critical_issues';
        }

        if ($completionPercentage < 70) {
            return 'incomplete';
        }

        if (! empty($warningIssues)) {
            return 'has_warnings';
        }

        return 'valid';
    }
}
