<?php

use App\Models\User;
use App\Models\WorkEntry;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

it('includes daily_hours array with date & hours for productivity endpoint', function () {
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

    // Create deterministic entries across 3 days
    for ($i = 3; $i >= 1; $i--) {
        $start = now()->subDays($i)->setTime(9, 0);
        WorkEntry::factory()->forUser($admin)->state([
            'start_date_time' => $start,
            'end_date_time' => (clone $start)->addHours(2 + $i), // variable hours
            'status' => 'completed',
        ])->create();
    }

    $response = get('/admin/insights/productivity');
    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/insights/Productivity')
        ->has('insights.daily_hours', fn (Assert $hours) => $hours
            ->where('0.date', now()->subDays(3)->toDateString())
            ->where('1.date', now()->subDays(2)->toDateString())
            ->where('2.date', now()->subDay()->toDateString())
            ->has('0.hours')
            ->has('1.hours')
            ->has('2.hours')
        )
    );
});
