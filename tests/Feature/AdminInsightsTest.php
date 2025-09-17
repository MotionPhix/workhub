<?php

use App\Models\User;
use App\Models\WorkEntry;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

it('loads admin system insights', function () {
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    // Ensure the user has the required permission
    // Ensure permissions exist
    foreach (['access-admin-panel', 'view-system-insights'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }

    // Ensure admin role exists & has permissions expected by route middleware
    $role = Role::firstOrCreate(['name' => 'admin']);
    $role->givePermissionTo(['access-admin-panel', 'view-system-insights']);

    $admin->assignRole('admin');
    $this->actingAs($admin);

    // Seed some work entries
    WorkEntry::factory()->create([
        'user_id' => $admin->id,
        'work_title' => 'Initial Planning',
        'start_date_time' => now()->subDays(2)->setTime(9, 0),
        'end_date_time' => now()->subDays(2)->setTime(12, 0),
        'status' => 'completed',
    ]);
    WorkEntry::factory()->create([
        'user_id' => $admin->id,
        'work_title' => 'Execution Block',
        'start_date_time' => now()->subDay()->setTime(10, 0),
        'end_date_time' => now()->subDay()->setTime(15, 0),
        'status' => 'completed',
    ]);

    $response = get('/admin/insights');

    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/insights/Index')
        ->has('insights', fn (Assert $insights) => $insights
            ->has('range')
            ->has('totals', fn (Assert $totals) => $totals->has('hours')->has('average_daily_hours'))
            ->has('trend')
            ->has('top_days')
            ->has('recommended_hours')
            ->has('daily_hours')
            ->has('departments')
        )
    );
});
