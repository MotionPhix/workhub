<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // ================================
        // ADMIN PANEL & SYSTEM PERMISSIONS
        // ================================
        $adminPermissions = [
            'access-admin-panel',
            'view-system-dashboard',
            'manage-system-settings',
            'view-system-logs',
            'manage-system-maintenance',
        ];

        // ================================
        // USER MANAGEMENT PERMISSIONS
        // ================================
        $userPermissions = [
            'view-users',
            'view-any-user',
            'create-users',
            'edit-users',
            'edit-any-user',
            'delete-users',
            'delete-any-user',
            'activate-users',
            'deactivate-users',
            'impersonate-users',
            'assign-user-roles',
            'manage-user-permissions',
        ];

        // ================================
        // INVITATION MANAGEMENT PERMISSIONS
        // ================================
        $invitationPermissions = [
            'view-invitations',
            'create-invitations',
            'edit-invitations',
            'delete-invitations',
            'resend-invitations',
            'cancel-invitations',
            'bulk-invite-users',
            'manage-invitation-settings',
        ];

        // ================================
        // ROLE & PERMISSION MANAGEMENT
        // ================================
        $rolePermissions = [
            'view-roles',
            'create-roles',
            'edit-roles',
            'delete-roles',
            'assign-permissions-to-roles',
            'view-permissions',
            'create-permissions',
            'edit-permissions',
            'delete-permissions',
        ];

        // ================================
        // DEPARTMENT MANAGEMENT PERMISSIONS
        // ================================
        $departmentPermissions = [
            'view-departments',
            'view-any-department',
            'create-departments',
            'edit-departments',
            'edit-any-department',
            'delete-departments',
            'delete-any-department',
            'assign-users-to-departments',
            'manage-department-hierarchy',
        ];

        // ================================
        // WORK ENTRY PERMISSIONS
        // ================================
        $workEntryPermissions = [
            // Own work entries
            'view-own-work-entries',
            'create-work-entries',
            'edit-own-work-entries',
            'delete-own-work-entries',

            // Team work entries (for managers)
            'view-team-work-entries',
            'edit-team-work-entries',
            'delete-team-work-entries',
            'approve-team-work-entries',
            'reject-team-work-entries',

            // All work entries (for admins)
            'view-all-work-entries',
            'edit-all-work-entries',
            'delete-all-work-entries',
            'approve-all-work-entries',
            'bulk-manage-work-entries',
        ];

        // ================================
        // REPORTING & ANALYTICS PERMISSIONS
        // ================================
        $reportingPermissions = [
            // Personal reports
            'view-own-reports',
            'generate-own-reports',
            'export-own-reports',

            // Team reports (for managers)
            'view-team-reports',
            'generate-team-reports',
            'export-team-reports',

            // System reports (for admins)
            'view-all-reports',
            'view-system-reports',
            'generate-system-reports',
            'export-system-reports',
            'schedule-reports',
            'manage-report-templates',

            // Advanced analytics
            'view-productivity-insights',
            'view-team-analytics',
            'view-system-analytics',
            'create-custom-reports',
            'manage-dashboard-widgets',
        ];

        // ================================
        // INSIGHTS & ANALYTICS PERMISSIONS
        // ================================
        $insightPermissions = [
            'view-personal-insights',
            'view-team-insights',
            'view-system-insights',
            'view-productivity-metrics',
            'view-performance-analytics',
            'create-insight-reports',
            'export-insights',
            'configure-insight-alerts',
        ];

        // ================================
        // PROFILE & SETTINGS PERMISSIONS
        // ================================
        $profilePermissions = [
            'view-own-profile',
            'edit-own-profile',
            'delete-own-profile',
            'view-any-profile',
            'edit-any-profile',
            'manage-profile-settings',
            'change-own-password',
            'force-password-change',
        ];

        // ================================
        // NOTIFICATION PERMISSIONS
        // ================================
        $notificationPermissions = [
            'view-notifications',
            'create-notifications',
            'send-notifications',
            'broadcast-notifications',
            'manage-notification-templates',
            'configure-notification-settings',
        ];

        // ================================
        // AUDIT & COMPLIANCE PERMISSIONS
        // ================================
        $auditPermissions = [
            'view-audit-logs',
            'view-user-activity',
            'view-system-activity',
            'export-audit-logs',
            'manage-compliance-reports',
            'configure-audit-settings',
        ];

        // ================================
        // PROJECT MANAGEMENT PERMISSIONS
        // ================================
        $projectPermissions = [
            'view-projects',
            'view-any-project',
            'create-projects',
            'edit-projects',
            'edit-any-project',
            'delete-projects',
            'delete-any-project',
            'archive-projects',
            'manage-project-teams',
            'assign-project-managers',
            'view-project-reports',
            'export-project-data',
        ];

        // Create all permissions
        $allPermissions = array_merge(
            $adminPermissions,
            $userPermissions,
            $invitationPermissions,
            $rolePermissions,
            $departmentPermissions,
            $workEntryPermissions,
            $reportingPermissions,
            $insightPermissions,
            $profilePermissions,
            $notificationPermissions,
            $auditPermissions,
            $projectPermissions
        );

        foreach ($allPermissions as $permission) {
            Permission::firstOrCreate(['name' => $permission]);
        }

        // ================================
        // CREATE ROLES AND ASSIGN PERMISSIONS
        // ================================

        // EMPLOYEE ROLE - Basic work entry capabilities
        $employeeRole = Role::firstOrCreate(['name' => 'employee']);
        $employeeRole->syncPermissions([
            // Work entries
            'view-own-work-entries',
            'create-work-entries',
            'edit-own-work-entries',
            'delete-own-work-entries',

            // Profile
            'view-own-profile',
            'edit-own-profile',
            'change-own-password',

            // Reports (own)
            'view-own-reports',
            'generate-own-reports',
            'export-own-reports',

            // Insights (personal)
            'view-personal-insights',

            // Departments (view only)
            'view-departments',

            // Notifications
            'view-notifications',
        ]);

        // MANAGER ROLE - Team management capabilities
        $managerRole = Role::firstOrCreate(['name' => 'manager']);
        $managerRole->syncPermissions([
            // All employee permissions
            ...$employeeRole->permissions->pluck('name')->toArray(),

            // Team work entries
            'view-team-work-entries',
            'edit-team-work-entries',
            'approve-team-work-entries',
            'reject-team-work-entries',

            // Team reports
            'view-team-reports',
            'generate-team-reports',
            'export-team-reports',

            // Team insights
            'view-team-insights',
            'view-productivity-metrics',

            // Department management (limited)
            'view-any-department',
            'edit-departments',

            // User management (limited)
            'view-users',

            // Invitation management (team level)
            'create-invitations',
            'view-invitations',
            'resend-invitations',
            'cancel-invitations',

            // Project management (team level)
            'view-projects',
            'create-projects',
            'edit-projects',
            'archive-projects',
            'manage-project-teams',

            // Notifications
            'create-notifications',
            'send-notifications',
        ]);

        // ADMIN ROLE - Full system access
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        $adminRole->syncPermissions(Permission::all());

        // ================================
        // CREATE DEFAULT ADMIN USER
        // ================================

        $adminUser = User::firstOrCreate(
            ['email' => 'admin@workhub.local'],
            [
                'name' => 'System Administrator',
                'email' => 'admin@workhub.local',
                'password' => Hash::make('AdminPass123!'),
                'email_verified_at' => now(),
            ]
        );

        // Assign admin role to the admin user
        $adminUser->assignRole('admin');

        // ================================
        // CREATE DEMO MANAGER USER
        // ================================

        $managerUser = User::firstOrCreate(
            ['email' => 'manager@workhub.local'],
            [
                'name' => 'Demo Manager',
                'email' => 'manager@workhub.local',
                'password' => Hash::make('ManagerPass123!'),
                'email_verified_at' => now(),
            ]
        );

        $managerUser->assignRole('manager');

        // ================================
        // CREATE DEMO EMPLOYEE USER
        // ================================

        $employeeUser = User::firstOrCreate(
            ['email' => 'employee@workhub.local'],
            [
                'name' => 'Demo Employee',
                'email' => 'employee@workhub.local',
                'password' => Hash::make('EmployeePass123!'),
                'email_verified_at' => now(),
            ]
        );

        $employeeUser->assignRole('employee');

        $this->command->info('✅ Roles and permissions seeded successfully!');
        $this->command->info('🔐 Admin User: admin@workhub.local / AdminPass123!');
        $this->command->info('👨‍💼 Manager User: manager@workhub.local / ManagerPass123!');
        $this->command->info('👨‍💻 Employee User: employee@workhub.local / EmployeePass123!');
    }
}
