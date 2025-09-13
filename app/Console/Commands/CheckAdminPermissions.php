<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class CheckAdminPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'admin:check-permissions';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check admin user permissions and system status';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🔍 Checking Admin Permissions System...');
        $this->line('');

        // Check if admin user exists
        $admin = User::where('email', 'admin@workhub.local')->first();

        if (! $admin) {
            $this->error('❌ Admin user not found!');

            return 1;
        }

        $this->info("✅ Admin user found: {$admin->name} ({$admin->email})");

        // Check roles
        $roles = $admin->roles;
        $this->info('📋 Roles: '.$roles->pluck('name')->implode(', '));

        // Check permissions count
        $permissionCount = $admin->getAllPermissions()->count();
        $totalPermissions = Permission::count();
        $this->info("🔑 Permissions: {$permissionCount}/{$totalPermissions}");

        // Check critical admin permissions
        $criticalPermissions = [
            'access-admin-panel',
            'view-system-dashboard',
            'view-users',
            'create-users',
            'edit-users',
            'delete-users',
            'view-invitations',
            'create-invitations',
            'view-system-reports',
            'view-system-insights',
        ];

        $this->line('');
        $this->info('🎯 Critical Admin Permissions Check:');

        foreach ($criticalPermissions as $permission) {
            $hasPermission = $admin->can($permission);
            $status = $hasPermission ? '✅' : '❌';
            $this->line("  {$status} {$permission}");
        }

        // Check role breakdown
        $this->line('');
        $this->info('📊 System Role Breakdown:');

        $allRoles = Role::withCount('users')->get();
        foreach ($allRoles as $role) {
            $this->line("  - {$role->name}: {$role->users_count} users");
        }

        $this->line('');
        $this->info('✨ Permission system check complete!');

        return 0;
    }
}
