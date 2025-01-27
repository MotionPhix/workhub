<?php

namespace App\Http\Controllers\Api\WorkLogs;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\WorkEntry;
use Illuminate\Http\Request;

class Latest extends Controller
{
  /**
   * Handle the incoming request.
   */
  public function __invoke(Request $request, User $user)
  {
    // Fetch the latest work logs for the user
    $workLogs = WorkEntry::where('user_id', $user->id)
      ->select(['id', 'uuid', 'work_date', 'hours_worked', 'work_title', 'description'])
      ->latest('work_date')
      ->limit(10)
      ->paginate(5);

    return response()->json($workLogs);
  }
}
