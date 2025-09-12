<?php

namespace App\Enums;

enum ReportType: string
{
    case SALES = 'sales';
    case MARKETING = 'marketing';
    case DESIGN = 'design';
    case BIDS_TENDERS = 'bids_tenders';
    case DEVELOPMENT = 'development';
    case GENERAL = 'general';

    public function getDisplayName(): string
    {
        return match ($this) {
            self::SALES => 'Sales Report',
            self::MARKETING => 'Marketing Report',
            self::DESIGN => 'Design Report',
            self::BIDS_TENDERS => 'Bids & Tenders Report',
            self::DEVELOPMENT => 'Development Report',
            self::GENERAL => 'General Report',
        };
    }

    public function getRequiredFields(): array
    {
        return match ($this) {
            self::SALES => [
                'leads_generated',
                'calls_made',
                'meetings_scheduled',
                'deals_closed',
                'revenue_generated',
                'conversion_rate',
                'pipeline_value',
            ],
            self::MARKETING => [
                'campaigns_launched',
                'leads_generated',
                'content_pieces_created',
                'social_media_engagement',
                'website_traffic',
                'email_open_rates',
                'ad_spend',
                'roi_metrics',
            ],
            self::DESIGN => [
                'designs_completed',
                'revisions_made',
                'client_feedback_score',
                'time_per_project',
                'creative_concepts',
                'brand_guidelines_followed',
                'software_used',
            ],
            self::BIDS_TENDERS => [
                'bids_submitted',
                'tenders_won',
                'proposal_value',
                'success_rate',
                'client_meetings',
                'documentation_prepared',
                'compliance_checks',
            ],
            self::DEVELOPMENT => [
                'features_completed',
                'bugs_fixed',
                'code_reviews_done',
                'tests_written',
                'deployment_frequency',
                'performance_improvements',
                'technical_debt_addressed',
            ],
            self::GENERAL => [
                'tasks_completed',
                'hours_worked',
                'meetings_attended',
                'goals_achieved',
            ],
        };
    }

    public function getMetricsConfig(): array
    {
        return match ($this) {
            self::SALES => [
                'primary_kpi' => 'revenue_generated',
                'secondary_kpis' => ['deals_closed', 'conversion_rate'],
                'target_fields' => ['revenue_target', 'deals_target'],
                'chart_types' => ['line', 'bar', 'funnel'],
            ],
            self::MARKETING => [
                'primary_kpi' => 'leads_generated',
                'secondary_kpis' => ['roi_metrics', 'engagement_rate'],
                'target_fields' => ['lead_target', 'roi_target'],
                'chart_types' => ['line', 'bar', 'pie'],
            ],
            self::DESIGN => [
                'primary_kpi' => 'designs_completed',
                'secondary_kpis' => ['client_feedback_score', 'time_efficiency'],
                'target_fields' => ['delivery_target', 'quality_target'],
                'chart_types' => ['bar', 'gauge', 'timeline'],
            ],
            self::BIDS_TENDERS => [
                'primary_kpi' => 'success_rate',
                'secondary_kpis' => ['proposal_value', 'tenders_won'],
                'target_fields' => ['win_rate_target', 'value_target'],
                'chart_types' => ['bar', 'funnel', 'heatmap'],
            ],
            self::DEVELOPMENT => [
                'primary_kpi' => 'features_completed',
                'secondary_kpis' => ['code_quality', 'velocity'],
                'target_fields' => ['sprint_target', 'quality_target'],
                'chart_types' => ['burndown', 'velocity', 'bar'],
            ],
            self::GENERAL => [
                'primary_kpi' => 'tasks_completed',
                'secondary_kpis' => ['hours_worked', 'efficiency'],
                'target_fields' => ['task_target', 'hour_target'],
                'chart_types' => ['bar', 'line', 'gauge'],
            ],
        };
    }

    public function getReportTemplate(): string
    {
        return match ($this) {
            self::SALES => 'reports.templates.sales',
            self::MARKETING => 'reports.templates.marketing',
            self::DESIGN => 'reports.templates.design',
            self::BIDS_TENDERS => 'reports.templates.bids-tenders',
            self::DEVELOPMENT => 'reports.templates.development',
            self::GENERAL => 'reports.templates.general',
        };
    }

    public static function getByDepartment(string $departmentName): self
    {
        return match (strtolower($departmentName)) {
            'sales' => self::SALES,
            'marketing' => self::MARKETING,
            'design', 'graphics', 'creative' => self::DESIGN,
            'bids', 'tenders', 'proposals' => self::BIDS_TENDERS,
            'development', 'dev', 'engineering' => self::DEVELOPMENT,
            default => self::GENERAL,
        };
    }
}
