<?php

use App\Models\User;
use App\Models\WorkEntry;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;

function seedPermissions(): void
{
    foreach (['access-admin-panel', 'view-system-insights'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }
}

function seedWorkEntries(User $user): void
{
    WorkEntry::factory()->count(3)->sequence(
        ['start_date_time' => now()->subDays(3)->setTime(9, 0), 'end_date_time' => now()->subDays(3)->setTime(12, 0), 'status' => 'completed'],
        ['start_date_time' => now()->subDays(2)->setTime(10, 0), 'end_date_time' => now()->subDays(2)->setTime(14, 30), 'status' => 'completed'],
        ['start_date_time' => now()->subDay()->setTime(11, 0), 'end_date_time' => now()->subDay()->setTime(16, 0), 'status' => 'completed'],
    )->create([
        'user_id' => $user->id,
    ]);
}

it('loads admin productivity insights endpoint', function () {
    $admin = makeAdminUser();
    $this->actingAs($admin);
    seedWorkEntries($admin);

    $response = get('/admin/insights/productivity');
    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/insights/Productivity')
        ->has('insights', fn (Assert $insights) => $insights
            ->where('period', '14d')
            ->has('average_daily_hours')
            ->has('trend')
            ->has('top_days')
            ->has('recommended_hours')
            ->has('daily_hours')
        )
    );
});

it('loads admin departments insights endpoint', function () {
    $admin = makeAdminUser();
    $this->actingAs($admin);
    seedWorkEntries($admin);

    $response = get('/admin/insights/departments');
    $response->assertSuccessful();

    $response->assertInertia(fn (Assert $page) => $page
        ->component('admin/insights/Departments')
        ->has('insights')
    );
});

// Permission matrix dataset: each missing permission scenario should yield forbidden (403)
dataset('missingSystemInsightPermissions', [
    'missing view-system-insights' => [['access-admin-panel']],
    'missing access-admin-panel' => [['view-system-insights']],
]);

it('forbids access to insights when permission missing', function (array $granted) {
    // Ensure base permissions exist first
    Permission::firstOrCreate(['name' => 'access-admin-panel']);
    Permission::firstOrCreate(['name' => 'view-system-insights']);

    $user = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    $role = Role::firstOrCreate(['name' => 'limited-admin']);
    $role->syncPermissions($granted); // only grant subset
    $user->assignRole($role->name);
    $this->actingAs($user);

    // Try each endpoint
    collect([
        '/admin/insights',
        '/admin/insights/productivity',
        '/admin/insights/departments',
    ])->each(function ($uri) {
        $resp = get($uri);
        $resp->assertForbidden();
    });
})->with('missingSystemInsightPermissions');
