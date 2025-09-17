<?php

use App\Models\Department;
use App\Models\User;
use App\Models\WorkEntry;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

it('returns departments with names and sorted by total_hours desc', function () {
    // Permissions & role
    foreach (['access-admin-panel', 'view-system-insights'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }
    $role = Role::firstOrCreate(['name' => 'admin']);
    $role->givePermissionTo(['access-admin-panel', 'view-system-insights']);

    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $admin->assignRole('admin');
    $this->actingAs($admin);

    // Create departments
    $deptA = Department::factory()->create(['name' => 'Alpha']);
    $deptB = Department::factory()->create(['name' => 'Beta']);
    $deptC = Department::factory()->create(['name' => 'Gamma']);

    // Seed entries with differing hour totals: Gamma highest, Alpha mid, Beta lowest
    // Gamma: 5 entries x 4h = 20h
    WorkEntry::factory()->count(5)->forDepartment($deptC->uuid)->state(function () {
        $start = now()->subDays(rand(1, 5))->setTime(9, 0);

        return [
            'start_date_time' => $start,
            'end_date_time' => (clone $start)->addHours(4),
            'status' => 'completed',
        ];
    })->create();

    // Alpha: 3 entries x 3h = 9h
    WorkEntry::factory()->count(3)->forDepartment($deptA->uuid)->state(function () {
        $start = now()->subDays(rand(1, 5))->setTime(10, 0);

        return [
            'start_date_time' => $start,
            'end_date_time' => (clone $start)->addHours(3),
            'status' => 'completed',
        ];
    })->create();

    // Beta: 2 entries x 2h = 4h
    WorkEntry::factory()->count(2)->forDepartment($deptB->uuid)->state(function () {
        $start = now()->subDays(rand(1, 5))->setTime(11, 0);

        return [
            'start_date_time' => $start,
            'end_date_time' => (clone $start)->addHours(2),
            'status' => 'completed',
        ];
    })->create();

    $response = get('/admin/insights/departments');
    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/insights/Departments')
        ->has('insights', 3)
        ->has('insights.0', fn (Assert $first) => $first
            ->where('department_name', 'Gamma')
            ->etc()
        )
    );
});
