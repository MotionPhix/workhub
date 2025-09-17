<?php

namespace App\Http\Controllers;

use App\Services\ProductivityInsightService;

class InsightsController extends Controller
{
    public function index()
    {
        $insights = (new ProductivityInsightService)
            ->generateInsights(auth()->id());

        return Inertia('insights/Index', [
            'insights' => $insights,
        ]);
    }
}
