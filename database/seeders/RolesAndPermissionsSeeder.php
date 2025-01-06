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

    // Create Roles and assign permissions
    $employeeRole = Role::create(['name' => 'employee'])
      ->givePermissionTo([
        'create work entries',
        'edit work entries'
      ]);

    $managerRole = Role::create(['name' => 'manager'])
      ->givePermissionTo([
        'view all work entries',
        'generate reports'
      ]);

    $adminRole = Role::create(['name' => 'admin'])
      ->givePermissionTo(Permission::all());
  }
}
