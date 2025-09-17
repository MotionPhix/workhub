<?php

use App\Models\Project;
use App\Models\User;
use App\Models\WorkEntry;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

use function Pest\Laravel\get;
use function Pest\Laravel\post;
use function Pest\Laravel\patch;

function actingAdmin() {
    foreach (['access-admin-panel'] as $perm) {
        Permission::firstOrCreate(['name' => $perm]);
    }
    $role = Role::firstOrCreate(['name' => 'admin']);
    $role->givePermissionTo(['access-admin-panel']);
    $admin = User::factory()->create([
        'email_verified_at' => now(),
        'is_active' => true,
    ]);
    $admin->assignRole('admin');
    test()->actingAs($admin);
    return $admin;
}

it('loads project index', function () {
    actingAdmin();
    Project::factory()->count(2)->create();

    $response = get('/projects');
    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page->component('projects/Index')->has('projects.data'));
});

it('shows a project detail page', function () {
    actingAdmin();
    $project = Project::factory()->active()->create();
    // Add some work entries to influence stats
    WorkEntry::factory()->count(3)->forDepartment($project->department_uuid)->state([
        'project_uuid' => $project->uuid,
        'status' => 'completed'
    ])->create();

    $response = get('/projects/'.$project->uuid);
    $response->assertSuccessful();
    $response->assertInertia(fn (Assert $page) => $page
        ->component('projects/Show')
        ->has('project')
        ->has('stats', fn (Assert $stats) => $stats->has('total_work_entries')->has('total_hours'))
    );
});

it('archives and reactivates a project', function () {
    actingAdmin();
    $project = Project::factory()->active()->create();

    $archiveResponse = patch('/projects/'.$project->uuid.'/archive');
    $archiveResponse->assertRedirect();
    $project->refresh();
    expect($project->status)->toBe('cancelled');

    $reactivateResponse = patch('/projects/'.$project->uuid.'/archive');
    $reactivateResponse->assertRedirect();
    $project->refresh();
    expect($project->status)->toBe('active');
});

it('updates progress via endpoint and returns json', function () {
    actingAdmin();
    $project = Project::factory()->active()->create();

    // Add mixed status entries
    WorkEntry::factory()->count(2)->forDepartment($project->department_uuid)->state([
        'project_uuid' => $project->uuid,
        'status' => 'completed'
    ])->create();
    WorkEntry::factory()->count(1)->forDepartment($project->department_uuid)->state([
        'project_uuid' => $project->uuid,
        'status' => 'in_progress'
    ])->create();

    $response = post('/projects/'.$project->uuid.'/update-progress');
    $response->assertSuccessful();
    $response->assertJsonStructure(['success', 'completion_percentage', 'status']);
});
