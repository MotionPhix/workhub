<?php

namespace App\Http\Controllers\Onboarding;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserOnboarding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CompleteStep extends Controller
{
  /**
   * Handle the incoming request.
   */
  public function __invoke(Request $request)
  {
    $validated = $request->validate([
      'step_id' => 'required|integer|min:1|max:4',
      'user_uuid' => 'required|uuid|exists:users,uuid'
    ]);

    try {
      DB::beginTransaction();

      $onboarding = UserOnboarding::firstOrCreate(
        ['user_uuid' => $validated['user_uuid']],
        [
          'completed_steps' => [],
          'is_completed' => false,
          'current_step' => 1,
          'completion_percentage' => 0
        ]
      );

      // Complete the step and update progress
      $onboarding->completeStep($validated['step_id']);

      // Update user status if onboarding is complete
      if ($onboarding->isComplete()) {
        User::where('uuid', $validated['user_uuid'])
          ->update(['onboarding_completed' => true]);
      }

      DB::commit();

      return response()->json([
        'message' => 'Step completed successfully',
        'onboarding' => $onboarding->fresh()
      ]);

    } catch (\Exception $e) {
      DB::rollBack();

      return response()->json([
        'message' => 'Failed to complete step'
      ], 500);
    }
  }
}
