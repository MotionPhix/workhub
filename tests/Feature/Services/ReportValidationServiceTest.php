<?php

use App\Enums\ReportType;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use App\Services\Report\ReportValidationService;
use Illuminate\Validation\ValidationException;

describe('ReportValidationService', function () {
    beforeEach(function () {
        $this->validationService = new ReportValidationService;
        $this->user = User::factory()->create();
        $this->department = Department::factory()->create();
    });

    describe('validateReportData', function () {
        it('validates sales report data successfully', function () {
            $data = [
                'title' => 'Weekly Sales Report',
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'leads_generated' => 100,
                'calls_made' => 50,
                'meetings_scheduled' => 25,
                'deals_closed' => 10,
                'revenue_generated' => 50000.00,
                'conversion_rate' => 10.0,
                'pipeline_value' => 150000.00,
            ];

            $result = $this->validationService->validateReportData($data, ReportType::SALES);

            expect($result)->toBeArray()
                ->and($result['title'])->toBe('Weekly Sales Report')
                ->and($result['leads_generated'])->toBe(100);
        });

        it('fails validation for missing required fields', function () {
            $data = [
                'title' => 'Incomplete Report',
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'leads_generated' => 100,
                // Missing other required fields
            ];

            expect(fn () => $this->validationService->validateReportData($data, ReportType::SALES))
                ->toThrow(ValidationException::class);
        });

        it('sanitizes malicious content', function () {
            $data = [
                'title' => 'Weekly Sales Report<script>alert("xss")</script>',
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'content' => '<p>Valid content</p><script>malicious()</script>',
                'leads_generated' => 100,
                'calls_made' => 50,
                'meetings_scheduled' => 25,
                'deals_closed' => 10,
                'revenue_generated' => 50000.00,
                'conversion_rate' => 10.0,
                'pipeline_value' => 150000.00,
            ];

            $result = $this->validationService->validateReportData($data, ReportType::SALES);

            expect($result['title'])->not->toContain('<script>')
                ->and($result['content'])->not->toContain('<script>');
        });

        it('validates business rules for sales reports', function () {
            $data = [
                'title' => 'Sales Report',
                'period_start' => '2024-01-01',
                'period_end' => '2024-01-07',
                'leads_generated' => 100,
                'calls_made' => 50,
                'meetings_scheduled' => 25,
                'deals_closed' => 20, // 20% conversion rate
                'revenue_generated' => 50000.00,
                'conversion_rate' => 15.0, // Incorrect rate (should be ~20%)
                'pipeline_value' => 150000.00,
            ];

            // Should pass validation but log a warning
            $result = $this->validationService->validateReportData($data, ReportType::SALES);
            expect($result)->toBeArray();
        });
    });

    describe('validateReportEntry', function () {
        it('validates report entry data successfully', function () {
            $data = [
                'title' => 'Daily Task Completion',
                'description' => 'Completed client presentations and follow-up calls',
                'entry_date' => '2024-01-01',
                'hours_worked' => 8.5,
                'priority' => 'high',
                'completion_status' => 'completed',
                'tags' => ['client-work', 'presentations'],
            ];

            $result = $this->validationService->validateReportEntry($data);

            expect($result)->toBeArray()
                ->and($result['title'])->toBe('Daily Task Completion')
                ->and($result['hours_worked'])->toBe(8.5);
        });

        it('fails validation for invalid priority', function () {
            $data = [
                'title' => 'Task',
                'description' => 'Description',
                'entry_date' => '2024-01-01',
                'hours_worked' => 8.0,
                'priority' => 'invalid-priority',
                'completion_status' => 'completed',
            ];

            expect(fn () => $this->validationService->validateReportEntry($data))
                ->toThrow(ValidationException::class);
        });
    });

    describe('performDataIntegrityCheck', function () {
        it('detects missing required metrics', function () {
            $report = Report::factory()->create([
                'report_type' => ReportType::SALES,
                'metrics_data' => [
                    'leads_generated' => 100,
                    // Missing other required fields
                ],
            ]);

            $issues = $this->validationService->performDataIntegrityCheck($report);

            expect($issues)->toHaveCount(1)
                ->and($issues[0]['type'])->toBe('missing_data')
                ->and($issues[0]['severity'])->toBe('warning');
        });

        it('detects suspicious patterns', function () {
            $report = Report::factory()->create([
                'report_type' => ReportType::SALES,
                'metrics_data' => [
                    'leads_generated' => 0,
                    'calls_made' => 0,
                    'meetings_scheduled' => 0,
                    'deals_closed' => 0,
                    'revenue_generated' => 0,
                    'conversion_rate' => 0,
                    'pipeline_value' => 0,
                ],
            ]);

            $issues = $this->validationService->performDataIntegrityCheck($report);

            expect($issues)->not->toBeEmpty()
                ->and(collect($issues)->pluck('type'))->toContain('suspicious_pattern');
        });
    });
});
