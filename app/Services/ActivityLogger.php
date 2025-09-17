<?php

namespace App\Services;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ActivityLogger
{
    public static function logWorkEntryAction($action, $entryId = null)
    {
        $user = Auth::user();

        Log::channel('work_entries')->info("{$user->name} performed {$action}", [
            'user_id' => $user->id,
            'entry_id' => $entryId,
            'ip_address' => request()->ip(),
        ]);
    }
}
