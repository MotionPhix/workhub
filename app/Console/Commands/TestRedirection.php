<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;

class TestRedirection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'test:redirection';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test user redirection logic based on roles';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing User Redirection Logic...');
        $this->line('');

        // Test admin redirection
        $admin = User::where('email', 'admin@workhub.local')->first();
        if ($admin) {
            $adminRedirect = $this->getRedirectUrl($admin);
            $this->info("👑 Admin ({$admin->email}) → {$adminRedirect}");
        }

        // Test manager redirection
        $manager = User::where('email', 'manager@workhub.local')->first();
        if ($manager) {
            $managerRedirect = $this->getRedirectUrl($manager);
            $this->info("👨‍💼 Manager ({$manager->email}) → {$managerRedirect}");
        }

        // Test employee redirection
        $employee = User::where('email', 'employee@workhub.local')->first();
        if ($employee) {
            $employeeRedirect = $this->getRedirectUrl($employee);
            $this->info("👨‍💻 Employee ({$employee->email}) → {$employeeRedirect}");
        }

        $this->line('');
        $this->info('✅ Redirection logic test complete!');

        return 0;
    }

    /**
     * Get the appropriate redirect URL for a user (mimics AuthController logic)
     */
    protected function getRedirectUrl(User $user): string
    {
        // Admin users go to admin dashboard
        if ($user->can('access-admin-panel')) {
            return route('admin.dashboard');
        }

        // Manager users go to regular dashboard with manager context
        if ($user->hasRole('manager')) {
            return route('dashboard').' (Manager View)';
        }

        // Regular employees go to their dashboard
        return route('dashboard').' (Employee View)';
    }
}
