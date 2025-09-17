<?php

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Project;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $departments = Department::all();
        $users = User::all();

        if ($departments->isEmpty() || $users->isEmpty()) {
            $this->command->warn('No departments or users found. Please run UserSeeder and DepartmentSeeder first.');

            return;
        }

        // Create sample client projects
        $clientProjects = [
            [
                'name' => 'Website Redesign for TechCorp',
                'description' => 'Complete redesign of corporate website with modern UI/UX',
                'project_type' => 'client',
                'client_name' => 'TechCorp Solutions',
                'client_contact' => 'john.smith@techcorp.com',
                'priority' => 'high',
                'estimated_hours' => 120,
                'status' => 'active',
                'completion_percentage' => 35,
            ],
            [
                'name' => 'Mobile App Development - RetailPlus',
                'description' => 'iOS and Android app for retail inventory management',
                'project_type' => 'client',
                'client_name' => 'RetailPlus Inc',
                'client_contact' => 'sarah.johnson@retailplus.com',
                'priority' => 'urgent',
                'estimated_hours' => 200,
                'status' => 'active',
                'completion_percentage' => 65,
            ],
            [
                'name' => 'E-commerce Platform Migration',
                'description' => 'Migrate legacy e-commerce platform to modern solution',
                'project_type' => 'client',
                'client_name' => 'ShopEasy Ltd',
                'client_contact' => 'mike.wilson@shopeasy.com',
                'priority' => 'medium',
                'estimated_hours' => 180,
                'status' => 'active',
                'completion_percentage' => 20,
            ],
            [
                'name' => 'Brand Identity Package',
                'description' => 'Complete brand identity design including logo, colors, and guidelines',
                'project_type' => 'client',
                'client_name' => 'StartupX',
                'client_contact' => 'founder@startupx.io',
                'priority' => 'medium',
                'estimated_hours' => 60,
                'status' => 'completed',
                'completion_percentage' => 100,
            ],
        ];

        // Create sample internal projects
        $internalProjects = [
            [
                'name' => 'Company Website Refresh',
                'description' => 'Update our company website with new content and features',
                'project_type' => 'internal',
                'priority' => 'low',
                'estimated_hours' => 40,
                'status' => 'active',
                'completion_percentage' => 15,
            ],
            [
                'name' => 'HR Management System',
                'description' => 'Internal system for managing employee data and workflows',
                'project_type' => 'internal',
                'priority' => 'high',
                'estimated_hours' => 160,
                'status' => 'active',
                'completion_percentage' => 45,
            ],
            [
                'name' => 'Marketing Campaign Q1',
                'description' => 'Design materials for Q1 marketing campaigns across all channels',
                'project_type' => 'internal',
                'priority' => 'medium',
                'estimated_hours' => 80,
                'status' => 'on_hold',
                'completion_percentage' => 10,
            ],
            [
                'name' => 'Office Space Redesign',
                'description' => 'Design new office layout and coordinate with contractors',
                'project_type' => 'internal',
                'priority' => 'low',
                'estimated_hours' => 30,
                'status' => 'draft',
                'completion_percentage' => 0,
            ],
        ];

        // Create client projects
        foreach ($clientProjects as $projectData) {
            $department = $departments->random();
            $manager = $users->where('department_uuid', $department->uuid)->first() ?? $users->random();

            Project::create(array_merge($projectData, [
                'department_uuid' => $department->uuid,
                'manager_id' => $manager->id,
                'start_date' => now()->subDays(rand(1, 60)),
                'due_date' => now()->addDays(rand(30, 120)),
                'actual_hours' => $projectData['estimated_hours'] * ($projectData['completion_percentage'] / 100),
            ]));
        }

        // Create internal projects
        foreach ($internalProjects as $projectData) {
            $department = $departments->random();
            $manager = $users->where('department_uuid', $department->uuid)->first() ?? $users->random();

            Project::create(array_merge($projectData, [
                'department_uuid' => $department->uuid,
                'manager_id' => $manager->id,
                'start_date' => now()->subDays(rand(1, 60)),
                'due_date' => $projectData['status'] !== 'draft' ? now()->addDays(rand(30, 120)) : null,
                'actual_hours' => $projectData['estimated_hours'] * ($projectData['completion_percentage'] / 100),
            ]));
        }

        $this->command->info('Project seeder completed successfully.');
        $this->command->info('Created '.count($clientProjects).' client projects and '.count($internalProjects).' internal projects.');
    }
}
