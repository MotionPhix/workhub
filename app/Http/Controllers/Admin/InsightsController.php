<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ProductivityInsightService;
use Inertia\Inertia;

class InsightsController extends Controller
{
    public function index()
    {
        // Admin-level system insights
        $insights = (new ProductivityInsightService)->generateSystemInsights();

        return Inertia::render('admin/insights/Index', [
            'insights' => $insights,
        ]);
    }

    public function productivity()
    {
        // System-wide productivity insights
        $insights = (new ProductivityInsightService)->getProductivityInsights();

        return Inertia::render('admin/insights/Productivity', [
            'insights' => $insights,
        ]);
    }

    public function departments()
    {
        // Department-level insights
        $insights = (new ProductivityInsightService)->getDepartmentInsights();

        return Inertia::render('admin/insights/Departments', [
            'insights' => $insights,
        ]);
    }
}
