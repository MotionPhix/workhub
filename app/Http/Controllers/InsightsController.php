<?php

namespace App\Http\Controllers;

use App\Services\ProductivityInsightService;
use Illuminate\Http\Request;

class InsightsController extends Controller
{
  public function index()
  {
    $insights = (new ProductivityInsightService())
      ->generateInsights(auth()->id());

    return Inertia('insights/Index', [
      'insights' => $insights
    ]);
  }
}
