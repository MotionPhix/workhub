<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
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

    // Create Permissions
    Permission::create(['name' => 'create work entries']);
    Permission::create(['name' => 'edit work entries']);
    Permission::create(['name' => 'delete work entries']);
    Permission::create(['name' => 'view all work entries']);
    Permission::create(['name' => 'generate reports']);

    Permission::create(['name' => 'create departments']);
    Permission::create(['name' => 'edit departments']);
    Permission::create(['name' => 'delete departments']);
    Permission::create(['name' => 'view departments']);
    Permission::create(['name' => 'assign departments']);

    // Create Roles and assign permissions
    $employeeRole = Role::create(['name' => 'employee'])
      ->givePermissionTo([
        'create work entries',
        'edit work entries',
        'view departments',
        'assign departments'
      ]);

    $managerRole = Role::create(['name' => 'managing director'])
      ->givePermissionTo([
        'view all work entries',
        'generate reports',
        'create departments',
        'edit departments',
        'delete departments',
        'view departments',
      ]);

    $generalManagerRole  = Role::create(['name' => 'general manager'])
      ->givePermissionTo([
        'view all work entries',
        'generate reports',
        'edit departments',
        'view departments',
      ]);

    $adminRole = Role::create(['name' => 'admin'])
      ->givePermissionTo(Permission::all());
  }
}
