<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DepartmentsSeeder extends Seeder
{
  /**
   * Run the database seeds.
   */
  public function run(): void
  {
    Department::create(['name' => 'HR', 'description' => 'Human Resources']);
    Department::create(['name' => 'IT', 'description' => 'Information Technology']);
    Department::create(['name' => 'Finance', 'description' => 'Finance Department']);
    Department::create(['name' => 'Media', 'description' => 'Graphic Designing and Wed Development']);
    Department::create(['name' => 'Operations', 'description' => 'Operations and Logistics']);
  }
}
