<?php

use App\Models\User;
use App\Models\WorkEntry;
use App\Notifications\WeeklyReportNotification;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

/*protected function schedule(Schedule $schedule)
{
  $schedule->call(function () {
    User::all()->each(function ($user) {
      // Send weekly report
      $user->notify(new WeeklyReportNotification());

      // Check for daily reminder
      $todayEntry = WorkEntry::where('user_id', $user->id)
        ->whereDate('work_date', today())
        ->exists();

      if (!$todayEntry) {
        $user->notify(new DailyReminderNotification());
      }
    });
  })->fridays()->at('16:30');
}*/
