<?php

namespace App\Services;

use App\Models\WorkEntry;

class ProductivityInsightService
{
    /**
     * System-wide insight aggregation used by Admin Insights index.
     */
    public function generateSystemInsights(): array
    {
        // Aggregate recent work entries (last 30 days) for system overview
        $query = WorkEntry::query();

        $recent = (clone $query)
            ->where('start_date_time', '>=', now()->subDays(30))
            ->with(['user:id,department_uuid'])
            ->get();

        $totalHours = $recent->sum('hours_worked');
        $avgDaily = $recent->groupBy(fn ($e) => $e->start_date_time->toDateString())
            ->map->sum('hours_worked')
            ->avg();
        $trend = $this->calculateProductivityTrend($recent);
        $dailyHours = $recent->groupBy(fn ($e) => $e->start_date_time->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'hours' => round($group->sum('hours_worked'), 2),
            ])->values();

        return [
            'range' => [
                'from' => now()->subDays(30)->toDateString(),
                'to' => now()->toDateString(),
            ],
            'totals' => [
                'hours' => round($totalHours, 2),
                'average_daily_hours' => round($avgDaily ?? 0, 2),
            ],
            'trend' => $trend,
            'daily_hours' => $dailyHours,
            'top_days' => $this->getMostProductiveDays($recent),
            'recommended_hours' => $this->recommendOptimalWorkHours($recent),
            'departments' => $this->getDepartmentInsights(),
        ];
    }

    /**
     * Alias kept for controller productivity() action.
     */
    public function getProductivityInsights(): array
    {
        $recent = WorkEntry::query()
            ->where('start_date_time', '>=', now()->subDays(14))
            ->get();

        $dailyHours = $recent->groupBy(fn ($e) => $e->start_date_time->toDateString())
            ->map(fn ($group, $date) => [
                'date' => $date,
                'hours' => round($group->sum('hours_worked'), 2),
            ])->values();

        return [
            'period' => '14d',
            'average_daily_hours' => round($recent->groupBy(fn ($e) => $e->start_date_time->toDateString())
                ->map->sum('hours_worked')
                ->avg() ?? 0, 2),
            'trend' => $this->calculateProductivityTrend($recent),
            'daily_hours' => $dailyHours,
            'top_days' => $this->getMostProductiveDays($recent),
            'recommended_hours' => $this->recommendOptimalWorkHours($recent),
        ];
    }

    /**
     * Basic department level aggregation (hours & entry counts last 30 days).
     */
    public function getDepartmentInsights(): array
    {
        // If WorkEntry has relationship to user -> department_id
        $recent = WorkEntry::with('user.department:id,uuid,name')
            ->where('start_date_time', '>=', now()->subDays(30))
            ->get();

        $byDept = $recent->groupBy(fn ($entry) => optional($entry->user)->department_uuid ?? 'unassigned');

        return $byDept->map(function ($group, $deptId) {
            $hours = $group->sum('hours_worked');
            $avg = $group->groupBy(fn ($e) => $e->start_date_time->toDateString())
                ->map->sum('hours_worked')
                ->avg();
            $deptName = $deptId === 'unassigned'
                ? 'Unassigned'
                : optional(optional($group->first())->user->department)->name;

            return [
                'department_id' => $deptId,
                'department_name' => $deptName,
                'total_hours' => round($hours, 2),
                'average_daily_hours' => round($avg ?? 0, 2),
                'entries' => $group->count(),
            ];
        })->values()->all();
    }

    public function generateInsights($userId)
    {
        $entries = WorkEntry::where('user_id', $userId)
            ->latest()
            ->limit(30)
            ->get();

        // Basic predictive analysis
        $insights = [
            'average_daily_hours' => $entries->avg('hours_worked'),
            'most_productive_days' => $this->getMostProductiveDays($entries),
            'productivity_trend' => $this->calculateProductivityTrend($entries),
            'recommended_work_hours' => $this->recommendOptimalWorkHours($entries),
        ];

        return $insights;
    }

    private function getMostProductiveDays($entries)
    {
        return $entries
            ->groupBy(fn ($e) => $e->start_date_time->toDateString())
            ->map(fn ($group) => $group->sum('hours_worked'))
            ->sortDesc()
            ->take(3);
    }

    private function calculateProductivityTrend($entries)
    {
        $hoursPerDay = $entries->groupBy(fn ($e) => $e->start_date_time->toDateString())
            ->map(fn ($group) => $group->sum('hours_worked'));

        // Simple linear regression
        $trend = $this->calculateLinearTrend($hoursPerDay);

        return $trend > 0 ? 'Improving' : 'Declining';
    }

    private function recommendOptimalWorkHours($entries)
    {
        $averageHours = $entries->avg('hours_worked');

        return round($averageHours, 1);
    }

    private function calculateLinearTrend($data)
    {
        if ($data->isEmpty()) {
            return 0;
        }

        // Convert the dates to numeric indices (e.g., sequential days)
        $keys = range(1, $data->count()); // Generate numeric indices (1, 2, 3, ...)
        $values = $data->values(); // Extract the work hours

        $n = count($keys);
        $sumX = array_sum($keys); // Sum of indices
        $sumY = $values->sum(); // Sum of work hours
        $sumXY = collect($keys)->zip($values)->map(fn ($pair) => $pair[0] * $pair[1])->sum(); // Sum of x * y
        $sumXSquared = collect($keys)->map(fn ($x) => $x * $x)->sum(); // Sum of x^2

        // Calculate denominator
        $denominator = $n * $sumXSquared - $sumX * $sumX;

        // Avoid division by zero
        if ($denominator == 0) {
            return 0; // Return 0 as the slope if there's no variance
        }

        // Calculate the slope
        $slope = ($n * $sumXY - $sumX * $sumY) / $denominator;

        return $slope;
    }
}
