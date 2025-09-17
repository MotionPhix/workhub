<?php

namespace App\Http\Controllers\Api\Insights;

use App\Http\Controllers\Controller;
use App\Services\ProductivityInsightService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\JsonResponse;

class Index extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): JsonResponse
    {
        $insights = (new ProductivityInsightService)
            ->generateInsights($request->user()->id);

        return response()->json($insights);
    }
}
