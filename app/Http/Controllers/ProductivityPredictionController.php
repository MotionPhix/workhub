<?php

namespace App\Http\Controllers;

use App\Services\AdvancedProductivityInsightService;
use Illuminate\Http\Request;

class ProductivityPredictionController extends Controller
{
  protected $insightService;

  public function __construct(AdvancedProductivityInsightService $insightService)
  {
    $this->insightService = $insightService;
  }

  public function getPredictions($userId)
  {
    $predictions = $this->insightService->generateComprehensiveInsights($userId)['predictive_productivity'];
    return response()->json($predictions);
  }
}
