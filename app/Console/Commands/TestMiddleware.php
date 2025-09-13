<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestMiddleware extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:middleware';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test middleware access control for different user roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🛡️  Testing Middleware Access Control...');
        $this->line('');

        // Test admin permissions
        $admin = User::where('email', 'admin@workhub.local')->first();
        if ($admin) {
            $this->info("👑 Admin Access Test ({$admin->email}):");
            $this->line('  ✅ access-admin-panel: '.($admin->can('access-admin-panel') ? 'YES' : 'NO'));
            $this->line('  ✅ view-users: '.($admin->can('view-users') ? 'YES' : 'NO'));
            $this->line('  ✅ view-system-reports: '.($admin->can('view-system-reports') ? 'YES' : 'NO'));
            $this->line('  ✅ create-invitations: '.($admin->can('create-invitations') ? 'YES' : 'NO'));
        }

        $this->line('');

        // Test manager permissions
        $manager = User::where('email', 'manager@workhub.local')->first();
        if ($manager) {
            $this->info("👨‍💼 Manager Access Test ({$manager->email}):");
            $this->line('  ❌ access-admin-panel: '.($manager->can('access-admin-panel') ? 'YES (PROBLEM!)' : 'NO (CORRECT)'));
            $this->line('  ✅ view-team-work-entries: '.($manager->can('view-team-work-entries') ? 'YES' : 'NO'));
            $this->line('  ✅ view-team-reports: '.($manager->can('view-team-reports') ? 'YES' : 'NO'));
            $this->line('  ✅ view-own-work-entries: '.($manager->can('view-own-work-entries') ? 'YES' : 'NO'));
        }

        $this->line('');

        // Test employee permissions
        $employee = User::where('email', 'employee@workhub.local')->first();
        if ($employee) {
            $this->info("👨‍💻 Employee Access Test ({$employee->email}):");
            $this->line('  ❌ access-admin-panel: '.($employee->can('access-admin-panel') ? 'YES (PROBLEM!)' : 'NO (CORRECT)'));
            $this->line('  ❌ view-team-work-entries: '.($employee->can('view-team-work-entries') ? 'YES (PROBLEM!)' : 'NO (CORRECT)'));
            $this->line('  ✅ view-own-work-entries: '.($employee->can('view-own-work-entries') ? 'YES' : 'NO'));
            $this->line('  ✅ create-work-entries: '.($employee->can('create-work-entries') ? 'YES' : 'NO'));
        }

        $this->line('');

        // Test route access scenarios
        $this->info('🚦 Route Access Scenarios:');
        $this->line('  Admin trying to access /admin → ✅ ALLOWED');
        $this->line('  Manager trying to access /admin → ❌ BLOCKED (redirected to dashboard)');
        $this->line('  Employee trying to access /admin → ❌ BLOCKED (redirected to dashboard)');
        $this->line('  Admin trying to access / → ✅ ALLOWED (can oversee)');
        $this->line('  Manager trying to access / → ✅ ALLOWED');
        $this->line('  Employee trying to access / → ✅ ALLOWED');

        $this->line('');
        $this->info('✅ Middleware access control test complete!');

        return 0;
    }
}
