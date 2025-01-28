<?php

namespace App\Http\Controllers\Onboarding;

use App\Enums\OnboardingStep;
use App\Http\Controllers\Controller;
use App\Models\UserOnboarding;
use Illuminate\Http\Request;

class Status extends Controller
{
  /**
   * Handle the incoming request.
   */
  public function __invoke(Request $request)
  {
    $user = $request->user();

    $onboarding = UserOnboarding::firstOrCreate(
      ['user_uuid' => $user->uuid],
      [
        'completed_steps' => [],
        'is_completed' => false,
        'current_step' => 1,
        'completion_percentage' => 0
      ]
    );

    // Add step details to response
    $response = $onboarding->toArray();
    $response['steps'] = $this->getStepDetails($onboarding->completed_steps ?? []);
    $response['next_step'] = $onboarding->getNextStep();

    return response()->json($onboarding);
  }

  protected function getStepDetails(array $completedSteps): array
  {
    return collect(OnboardingStep::getStepDetails())
      ->map(function ($step, $id) use ($completedSteps) {
        return array_merge($step, [
          'id' => $id,
          'completed' => in_array($id, $completedSteps)
        ]);
      })
      ->values()
      ->all();
  }
}
