<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // HR Department - Standard business hours with focus on people management
        Department::create([
            'name' => 'HR',
            'description' => 'Human Resources',
            'working_hours_per_day' => 8.0,
            'daily_tasks_target' => 5,
            'quality_target_percentage' => 90.0,
        ]);

        // IT Department - Longer hours due to technical demands and higher task volume
        Department::create([
            'name' => 'IT',
            'description' => 'Information Technology',
            'working_hours_per_day' => 8.5,
            'daily_tasks_target' => 7,
            'quality_target_percentage' => 95.0,
        ]);

        // Finance Department - Standard hours but extremely high quality requirements
        Department::create([
            'name' => 'Finance',
            'description' => 'Finance Department',
            'working_hours_per_day' => 8.0,
            'daily_tasks_target' => 4,
            'quality_target_percentage' => 98.0,
        ]);

        // Media Department - Flexible creative hours with lower task volume but creative output
        Department::create([
            'name' => 'Media',
            'description' => 'Graphic Designing and Web Development',
            'working_hours_per_day' => 7.5,
            'daily_tasks_target' => 3,
            'quality_target_percentage' => 85.0,
        ]);

        // Operations Department - Standard operational targets
        Department::create([
            'name' => 'Operations',
            'description' => 'Operations and Logistics',
            'working_hours_per_day' => 8.0,
            'daily_tasks_target' => 6,
            'quality_target_percentage' => 92.0,
        ]);
    }
}
