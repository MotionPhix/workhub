<?php

use App\Models\Report;
use App\Models\User;
use App\Services\Analytics\ManagerAnalyticsService;

describe('ManagerAnalyticsService', function () {
    beforeEach(function () {
        $this->analyticsService = new ManagerAnalyticsService;
        $this->manager = User::factory()->create(['email' => 'manager@company.com']);

        // Create team members
        User::factory()->count(3)->create([
            'manager_email' => $this->manager->email,
        ]);
    });

    describe('getManagerDashboardData', function () {
        it('returns complete dashboard data structure', function () {
            $data = $this->analyticsService->getManagerDashboardData($this->manager);

            expect($data)->toHaveKeys([
                'team_overview',
                'report_metrics',
                'performance_analytics',
                'compliance_status',
                'trending_insights',
                'upcoming_reports',
                'team_rankings',
            ]);
        });

        it('calculates team overview correctly', function () {
            $data = $this->analyticsService->getManagerDashboardData($this->manager);
            $teamOverview = $data['team_overview'];

            expect($teamOverview['total_members'])->toBe(3)
                ->and($teamOverview)->toHaveKey('active_members')
                ->and($teamOverview)->toHaveKey('activity_rate');
        });
    });

    describe('team performance analytics', function () {
        it('calculates individual performance metrics', function () {
            $teamMembers = User::where('manager_email', $this->manager->email)->get();

            // Create reports for team members
            foreach ($teamMembers as $member) {
                Report::factory()->count(2)->create([
                    'user_id' => $member->id,
                    'status' => 'approved',
                ]);

                Report::factory()->create([
                    'user_id' => $member->id,
                    'status' => 'pending',
                ]);
            }

            $data = $this->analyticsService->getManagerDashboardData($this->manager);
            $performance = $data['performance_analytics'];

            expect($performance['individual_performance'])->toHaveCount(3)
                ->and($performance['team_averages'])->toHaveKey('avg_reports_per_member')
                ->and($performance['top_performers'])->not->toBeEmpty();
        });
    });

    describe('compliance tracking', function () {
        it('identifies overdue team members', function () {
            $teamMembers = User::where('manager_email', $this->manager->email)->get();
            $overdueMembers = $teamMembers->take(2);

            // Create old reports for some members (overdue)
            foreach ($overdueMembers as $member) {
                Report::factory()->create([
                    'user_id' => $member->id,
                    'created_at' => now()->subDays(20),
                ]);
            }

            // Create recent report for one member (compliant)
            Report::factory()->create([
                'user_id' => $teamMembers->last()->id,
                'created_at' => now()->subDays(3),
            ]);

            $data = $this->analyticsService->getManagerDashboardData($this->manager);
            $compliance = $data['compliance_status'];

            expect($compliance['overdue_reports'])->toBeGreaterThan(0)
                ->and($compliance['compliance_rate'])->toBeLessThan(100);
        });
    });

    describe('team rankings', function () {
        it('ranks team members by performance score', function () {
            $teamMembers = User::where('manager_email', $this->manager->email)->get();

            // Create different performance levels
            Report::factory()->count(5)->create([
                'user_id' => $teamMembers->first()->id,
                'status' => 'approved',
            ]);

            Report::factory()->count(2)->create([
                'user_id' => $teamMembers->get(1)->id,
                'status' => 'approved',
            ]);

            // Third member has no reports

            $data = $this->analyticsService->getManagerDashboardData($this->manager);
            $rankings = $data['team_rankings'];

            expect($rankings['rankings'])->toHaveCount(3)
                ->and($rankings['rankings'][0]['score'])->toBeGreaterThan($rankings['rankings'][1]['score'])
                ->and($rankings['top_3'])->toHaveCount(3);
        });
    });

    describe('exportTeamAnalytics', function () {
        it('exports analytics data with metadata', function () {
            $exportData = $this->analyticsService->exportTeamAnalytics($this->manager);

            expect($exportData)->toHaveKeys([
                'export_timestamp',
                'manager',
                'team_size',
                'period',
                'analytics_data',
            ])
                ->and($exportData['manager']['email'])->toBe($this->manager->email)
                ->and($exportData['team_size'])->toBe(3);
        });
    });
});
