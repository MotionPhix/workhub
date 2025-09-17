<?php

namespace App\Console\Commands;

use App\Models\WorkEntry;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class CacheWorkStatistics extends Command
{
    protected $signature = 'work:cache-statistics';

    protected $description = 'Cache work entry statistics';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $stats = Cache::remember('work_statistics', now()->addHours(24), function () {
            return [
                'total_entries' => WorkEntry::count(),
                'total_hours' => WorkEntry::sum('hours_worked'),
                'average_daily_hours' => WorkEntry::avg('hours_worked'),
            ];
        });

        return 0;
    }
}
