<?php

use App\Models\Project;
use App\Models\Department;
use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Str;

function makeAdminUser(array $extra = []): User
{
    // Ensure permissions exist
    $permissions = [
        'access-admin-panel',
        'view-projects',
        'create-projects',
        'edit-projects',
        'delete-projects',
        'archive-projects',
        'view-system-insights',
    ];

    foreach ($permissions as $permission) {
        Permission::firstOrCreate(['name' => $permission]);
    }

    $user = User::factory()->create(array_merge([
        'email_verified_at' => now(),
        'is_active' => true,
    ], $extra));

    $role = Role::firstOrCreate(['name' => 'admin']);
    $role->givePermissionTo($permissions);
    $user->assignRole('admin');

    return $user;
}

function seedProjectPermissions(): void {
    $perms = [
        'access-admin-panel',
        'view-projects',
        'create-projects',
        'edit-projects',
        'delete-projects',
        'archive-projects',
    ];
    $role = Role::firstOrCreate(['name' => 'admin','guard_name' => 'web']);
    $role->givePermissionTo($perms);
}

it('lists projects (index)', function () {
    $user = makeAdminUser();
    Department::factory()->create();
    Project::factory()->count(3)->create();

    $this->actingAs($user)
        ->get(route('admin.projects.index'))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 3)
        );
});

it('shows a single project', function () {
    $user = makeAdminUser();
    $project = Project::factory()->create();

    $this->actingAs($user)
        ->get(route('admin.projects.show', $project->uuid))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Show')
            ->where('project.uuid', (string) $project->uuid)
            ->has('stats')
        );
});

it('validates store request', function () {
    $user = makeAdminUser();
    $this->actingAs($user)
        ->post(route('admin.projects.store'), [])
        ->assertSessionHasErrors(['name','department_uuid','manager_id','start_date','due_date','status','priority','project_type']);
});

it('creates a project', function () {
    $user = makeAdminUser();
    $dept = Department::factory()->create();
    $manager = User::factory()->create();

    $payload = [
        'name' => 'Test Project',
        'description' => 'Desc',
        'department_uuid' => $dept->uuid,
        'manager_id' => $manager->id,
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(5)->toDateString(),
        'status' => 'active',
        'priority' => 'medium',
        'project_type' => 'internal',
    ];

    $this->actingAs($user)
        ->post(route('admin.projects.store'), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'name' => 'Test Project',
        'status' => 'active',
    ]);
});

it('updates a project', function () {
    $user = makeAdminUser();
    $project = Project::factory()->create(['status' => 'active']);
    $dept = Department::factory()->create();
    $manager = User::factory()->create();

    $payload = [
        'name' => 'Updated',
        'description' => 'Updated Desc',
        'department_uuid' => $dept->uuid,
        'manager_id' => $manager->id,
        'start_date' => now()->toDateString(),
        'due_date' => now()->addDays(3)->toDateString(),
        'status' => 'completed',
        'priority' => 'high',
        'project_type' => 'internal',
    ];

    $this->actingAs($user)
        ->put(route('admin.projects.update', $project->uuid), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'name' => 'Updated',
        'status' => 'completed',
    ]);
});

it('archives and reactivates a project', function () {
    $user = makeAdminUser();
    $project = Project::factory()->create(['status' => 'active']);

    $this->actingAs($user)
        ->patch(route('admin.projects.archive', $project->uuid))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'status' => 'cancelled'
    ]);

    $this->actingAs($user)
        ->patch(route('admin.projects.archive', $project->uuid))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseHas('projects', [
        'id' => $project->id,
        'status' => 'active'
    ]);
});

it('updates project progress (json)', function () {
    $user = makeAdminUser();
    $project = Project::factory()->create(['completion_percentage' => 10]);

    $this->actingAs($user)
        ->post(route('admin.projects.update-progress', $project->uuid))
        ->assertSuccessful()
        ->assertJsonStructure(['success','completion_percentage','status']);
});

it('denies access without permissions', function () {
    $user = User::factory()->create(['is_active' => true]);
    $project = Project::factory()->create();
    $this->actingAs($user)
        ->get(route('admin.projects.index'))
        ->assertForbidden();
    $this->actingAs($user)
        ->get(route('admin.projects.show', $project->uuid))
        ->assertForbidden();
});

it('filters projects by search term', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different names
    Project::factory()->create(['name' => 'Alpha Project', 'description' => 'First project']);
    Project::factory()->create(['name' => 'Beta Project', 'description' => 'Second project']);
    Project::factory()->create(['name' => 'Gamma Project', 'description' => 'Third project']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', ['search' => 'Alpha']))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.name', 'Alpha Project')
        );
});

it('filters projects by status', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different statuses
    Project::factory()->create(['status' => 'active']);
    Project::factory()->create(['status' => 'completed']);
    Project::factory()->create(['status' => 'on_hold']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', ['status' => 'active']))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.status', 'active')
        );
});

it('filters projects by priority', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different priorities
    Project::factory()->create(['priority' => 'high']);
    Project::factory()->create(['priority' => 'medium']);
    Project::factory()->create(['priority' => 'low']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', ['priority' => 'high']))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.priority', 'high')
        );
});

it('filters projects by project type', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different types
    Project::factory()->create(['project_type' => 'internal']);
    Project::factory()->create(['project_type' => 'client']);
    Project::factory()->create(['project_type' => 'internal']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', ['project_type' => 'client']))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.project_type', 'client')
        );
});

it('combines multiple filters', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different combinations
    Project::factory()->create(['status' => 'active', 'priority' => 'high', 'project_type' => 'internal']);
    Project::factory()->create(['status' => 'completed', 'priority' => 'high', 'project_type' => 'internal']);
    Project::factory()->create(['status' => 'active', 'priority' => 'medium', 'project_type' => 'client']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', [
            'status' => 'active',
            'priority' => 'high',
            'project_type' => 'internal'
        ]))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 1)
            ->where('projects.data.0.status', 'active')
            ->where('projects.data.0.priority', 'high')
            ->where('projects.data.0.project_type', 'internal')
        );
});

it('returns all projects when filter is set to all', function () {
    $user = makeAdminUser();
    Department::factory()->create();

    // Create projects with different statuses
    Project::factory()->create(['status' => 'active']);
    Project::factory()->create(['status' => 'completed']);
    Project::factory()->create(['status' => 'on_hold']);

    $this->actingAs($user)
        ->get(route('admin.projects.index', ['status' => 'all']))
        ->assertInertia(fn($page) => $page
            ->component('admin/projects/Index')
            ->has('projects.data', 3)
        );
});
