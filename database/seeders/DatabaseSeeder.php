<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Support\Facades\Hash;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
  /**
   * Seed the application's database.
   */
  public function run(): void
  {
    $this->call([
      RolesAndPermissionsSeeder::class,
      DepartmentsSeeder::class,
    ]);

    // Create an admin user for testing the invitation flow
    $itDepartment = \App\Models\Department::where('name', 'IT')->first();
    
    $user = User::factory()->create([
      'name' => 'Admin User',
      'email' => 'admin@workhub.com',
      'department_uuid' => $itDepartment?->uuid,
      'password' => Hash::make('Pa$$w0rd'), // Explicit password for testing
    ]);

    $user->syncRoles('admin');
    
    $this->command->info('✅ Admin user created:');
    $this->command->line('   Email: admin@workhub.com');  
    $this->command->line('   Password: Pa$$w0rd');
  }
}
