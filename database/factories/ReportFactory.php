<?php

namespace Database\Factories;

use App\Enums\ReportType;
use App\Models\Department;
use App\Models\Report;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class ReportFactory extends Factory
{
    protected $model = Report::class;

    public function definition(): array
    {
        $reportType = $this->faker->randomElement(ReportType::cases());
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 week');

        return [
            'user_id' => User::factory(),
            'department_id' => Department::factory(),
            'report_type' => $reportType,
            'title' => $this->generateTitle($reportType),
            'period_start' => $startDate,
            'period_end' => $endDate,
            'metrics_data' => $this->generateMetricsData($reportType),
            'content' => $this->faker->paragraphs(3, true),
            'status' => $this->faker->randomElement(['draft', 'pending', 'approved', 'rejected', 'sent']),
            'recipient_emails' => [$this->faker->email(), $this->faker->email()],
            'template_version' => '1.0',
            'settings' => [
                'auto_generated' => $this->faker->boolean(),
                'priority' => $this->faker->randomElement(['low', 'medium', 'high']),
            ],
        ];
    }

    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'submitted_at' => null,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'pending',
            'submitted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
        ]);
    }

    public function approved(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'approved',
            'submitted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approved_at' => $this->faker->dateTimeBetween($attributes['submitted_at'] ?? '-1 week', 'now'),
            'approved_by' => User::factory(),
        ]);
    }

    public function sent(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'sent',
            'submitted_at' => $this->faker->dateTimeBetween('-1 week', 'now'),
            'approved_at' => $this->faker->dateTimeBetween($attributes['submitted_at'] ?? '-1 week', 'now'),
            'approved_by' => User::factory(),
            'sent_at' => $this->faker->dateTimeBetween($attributes['approved_at'] ?? '-1 week', 'now'),
        ]);
    }

    public function sales(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => ReportType::SALES,
            'metrics_data' => $this->generateSalesMetrics(),
        ]);
    }

    public function marketing(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => ReportType::MARKETING,
            'metrics_data' => $this->generateMarketingMetrics(),
        ]);
    }

    public function design(): static
    {
        return $this->state(fn (array $attributes) => [
            'report_type' => ReportType::DESIGN,
            'metrics_data' => $this->generateDesignMetrics(),
        ]);
    }

    private function generateTitle(ReportType $reportType): string
    {
        $types = [
            ReportType::SALES => 'Sales Performance Report',
            ReportType::MARKETING => 'Marketing Campaign Report',
            ReportType::DESIGN => 'Design Deliverables Report',
            ReportType::BIDS_TENDERS => 'Bids & Tenders Report',
            ReportType::DEVELOPMENT => 'Development Sprint Report',
            ReportType::GENERAL => 'General Activity Report',
        ];

        return $types[$reportType].' - '.$this->faker->dateTimeBetween('-1 month', 'now')->format('M Y');
    }

    private function generateMetricsData(ReportType $reportType): array
    {
        return match ($reportType) {
            ReportType::SALES => $this->generateSalesMetrics(),
            ReportType::MARKETING => $this->generateMarketingMetrics(),
            ReportType::DESIGN => $this->generateDesignMetrics(),
            ReportType::BIDS_TENDERS => $this->generateBidsMetrics(),
            ReportType::DEVELOPMENT => $this->generateDevelopmentMetrics(),
            ReportType::GENERAL => $this->generateGeneralMetrics(),
        };
    }

    private function generateSalesMetrics(): array
    {
        $leads = $this->faker->numberBetween(50, 500);
        $deals = $this->faker->numberBetween(5, $leads / 5);

        return [
            'leads_generated' => $leads,
            'calls_made' => $this->faker->numberBetween($leads / 2, $leads),
            'meetings_scheduled' => $this->faker->numberBetween($deals, $leads / 3),
            'deals_closed' => $deals,
            'revenue_generated' => $this->faker->numberBetween(10000, 500000),
            'conversion_rate' => round(($deals / $leads) * 100, 2),
            'pipeline_value' => $this->faker->numberBetween(50000, 1000000),
        ];
    }

    private function generateMarketingMetrics(): array
    {
        return [
            'campaigns_launched' => $this->faker->numberBetween(1, 10),
            'leads_generated' => $this->faker->numberBetween(100, 1000),
            'content_pieces_created' => $this->faker->numberBetween(5, 50),
            'social_media_engagement' => $this->faker->numberBetween(1000, 50000),
            'website_traffic' => $this->faker->numberBetween(5000, 100000),
            'email_open_rates' => $this->faker->numberBetween(15, 45),
            'ad_spend' => $this->faker->numberBetween(1000, 50000),
            'roi_metrics' => $this->faker->numberBetween(-20, 300),
        ];
    }

    private function generateDesignMetrics(): array
    {
        return [
            'designs_completed' => $this->faker->numberBetween(5, 50),
            'revisions_made' => $this->faker->numberBetween(10, 100),
            'client_feedback_score' => $this->faker->numberBetween(6, 10),
            'time_per_project' => $this->faker->numberBetween(2, 40),
            'creative_concepts' => $this->faker->numberBetween(10, 30),
            'brand_guidelines_followed' => $this->faker->numberBetween(80, 100),
            'software_used' => $this->faker->randomElements(['Photoshop', 'Illustrator', 'Figma', 'Sketch', 'InDesign'], $this->faker->numberBetween(1, 3)),
        ];
    }

    private function generateBidsMetrics(): array
    {
        $bids = $this->faker->numberBetween(1, 20);
        $won = $this->faker->numberBetween(0, $bids);

        return [
            'bids_submitted' => $bids,
            'tenders_won' => $won,
            'proposal_value' => $this->faker->numberBetween(100000, 5000000),
            'success_rate' => $bids > 0 ? round(($won / $bids) * 100, 2) : 0,
            'client_meetings' => $this->faker->numberBetween($bids, $bids * 3),
            'documentation_prepared' => $this->faker->numberBetween($bids, $bids * 5),
            'compliance_checks' => $this->faker->numberBetween($bids, $bids * 2),
        ];
    }

    private function generateDevelopmentMetrics(): array
    {
        return [
            'features_completed' => $this->faker->numberBetween(5, 30),
            'bugs_fixed' => $this->faker->numberBetween(10, 100),
            'code_reviews_done' => $this->faker->numberBetween(20, 80),
            'tests_written' => $this->faker->numberBetween(50, 300),
            'deployment_frequency' => $this->faker->numberBetween(1, 20),
            'performance_improvements' => $this->faker->numberBetween(1, 10),
            'technical_debt_addressed' => $this->faker->numberBetween(0, 15),
        ];
    }

    private function generateGeneralMetrics(): array
    {
        return [
            'tasks_completed' => $this->faker->numberBetween(20, 100),
            'hours_worked' => $this->faker->numberBetween(30, 160),
            'meetings_attended' => $this->faker->numberBetween(5, 30),
            'goals_achieved' => $this->faker->numberBetween(1, 10),
        ];
    }
}
