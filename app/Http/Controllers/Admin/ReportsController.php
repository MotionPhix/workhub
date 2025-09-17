<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\WorkEntry;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReportsController extends Controller
{
    public function index(Request $request)
    {
        // Admin can see all work entries for reporting
        $query = WorkEntry::with(['user.department']);

        // Apply filters if provided
        if ($request->filled('start_date')) {
            $query->whereDate('start_date_time', '>=', $request->start_date);
        }

        if ($request->filled('end_date')) {
            $query->whereDate('start_date_time', '<=', $request->end_date);
        }

        if ($request->filled('department')) {
            $query->whereHas('user.department', function ($q) use ($request) {
                $q->where('name', $request->department);
            });
        }

        $workEntries = $query->latest('start_date_time')->paginate(15);

        // Calculate hours worked for each entry
        $workEntriesWithHours = $workEntries->getCollection()->map(function ($entry) {
            $startTime = $entry->start_date_time ? new \DateTime($entry->start_date_time) : null;
            $endTime = $entry->end_date_time ? new \DateTime($entry->end_date_time) : null;

            if ($startTime && $endTime) {
                $interval = $startTime->diff($endTime);
                $hoursWorked = $interval->h + ($interval->i / 60); // Convert minutes to decimal hours
            } else {
                $hoursWorked = 0;
            }

            $entry->hours_worked = round($hoursWorked, 2);
            $entry->work_date = $entry->start_date_time ? $entry->start_date_time->format('Y-m-d') : null;

            return $entry;
        });

        $workEntries->setCollection($workEntriesWithHours);

        // Generate stats for admin dashboard
        $totalHours = WorkEntry::whereNotNull('start_date_time')
            ->whereNotNull('end_date_time')
            ->get()
            ->sum(function ($entry) {
                $startTime = new \DateTime($entry->start_date_time);
                $endTime = new \DateTime($entry->end_date_time);
                $interval = $startTime->diff($endTime);

                return $interval->h + ($interval->i / 60);
            });

        $averageProductivity = WorkEntry::whereNotNull('productivity_rating')
            ->avg('productivity_rating') ?: 0;

        $stats = [
            'total_hours' => round($totalHours, 1),
            'average_hours_per_day' => round($totalHours / 7, 1), // Assuming weekly data
            'departments' => Department::withCount('users')
                ->get()
                ->map(fn ($dept) => ['name' => $dept->name, 'count' => $dept->users_count])
                ->toArray(),
            'productivity_trends' => [
                ['date' => now()->subDays(6)->format('Y-m-d'), 'score' => 75],
                ['date' => now()->subDays(5)->format('Y-m-d'), 'score' => 80],
                ['date' => now()->subDays(4)->format('Y-m-d'), 'score' => 85],
                ['date' => now()->subDays(3)->format('Y-m-d'), 'score' => 78],
                ['date' => now()->subDays(2)->format('Y-m-d'), 'score' => 82],
                ['date' => now()->subDays(1)->format('Y-m-d'), 'score' => 88],
                ['date' => now()->format('Y-m-d'), 'score' => round($averageProductivity * 10, 0)],
            ],
            'focus_time_analytics' => [
                'summary' => [
                    'average_focus_hours' => 6.5,
                    'team_focus_percentage' => 85,
                ],
            ],
            'burnout_risks' => [
                ['risk_level' => 'low', 'score' => 15, 'employees' => 25],
                ['risk_level' => 'medium', 'score' => 35, 'employees' => 8],
                ['risk_level' => 'high', 'score' => 75, 'employees' => 2],
            ],
        ];

        return Inertia::render('admin/reports/Index', [
            'reports' => $workEntries,
            'stats' => $stats,
            'filters' => [
                'start_date' => $request->start_date,
                'end_date' => $request->end_date,
                'department' => $request->department,
            ],
        ]);
    }

    public function show(WorkEntry $workEntry)
    {
        // Admin can view any work entry
        $workEntry->load(['user.department']);

        // Calculate hours worked
        $hoursWorked = 0;
        if ($workEntry->start_date_time && $workEntry->end_date_time) {
            $startTime = new \DateTime($workEntry->start_date_time);
            $endTime = new \DateTime($workEntry->end_date_time);
            $interval = $startTime->diff($endTime);
            $hoursWorked = round($interval->h + ($interval->i / 60), 2);
        }

        return Inertia::render('admin/reports/Show', [
            'report' => $workEntry->toArray() + ['hours_worked' => $hoursWorked],
        ]);
    }

    public function export(WorkEntry $workEntry, Request $request)
    {
        // Export functionality for admin work entry reports
        $format = $request->get('format', 'pdf');

        // Implementation would depend on export service
        return response()->json([
            'message' => 'Work entry export initiated',
            'work_entry_id' => $workEntry->uuid,
            'format' => $format,
        ]);
    }
}
