<?php

use App\Models\Department;
use App\Models\User;
use App\Models\UserInvite;
use App\Services\Auth\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);

    // Create necessary permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'view-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'create-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'edit-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'delete-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'access-admin-panel']);

    // Create test admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    // Give admin role the necessary permissions
    $adminRole = Role::where('name', 'admin')->first();
    $adminRole->givePermissionTo(['view-invitations', 'create-invitations', 'edit-invitations', 'delete-invitations', 'access-admin-panel']);

    // Also explicitly give permissions to the user to ensure they're loaded
    $this->admin->givePermissionTo(['view-invitations', 'create-invitations', 'edit-invitations', 'delete-invitations', 'access-admin-panel']);

    // Refresh the user to ensure permissions are loaded
    $this->admin->refresh();

    // Refresh the user to ensure permissions are loaded
    $this->admin->refresh();

    // Create test department
    $this->department = Department::factory()->create();

    // Create invitation service
    $this->invitationService = app(InvitationService::class);
});

it('allows admin to send invitation', function () {
    $invitationData = [
        'email' => 'newuser@example.com',
        'name' => 'New User',
        'department_uuid' => $this->department->uuid,
        'role_name' => 'employee',
        'expires_in_days' => 7,
    ];

    $invitation = $this->invitationService->sendInvitation($invitationData, $this->admin);

    expect($invitation)
        ->toBeInstanceOf(UserInvite::class)
        ->and($invitation->email)->toBe('newuser@example.com')
        ->and($invitation->status)->toBe('pending')
        ->and($invitation->isPending())->toBeTrue();
});

it('prevents duplicate invitations for same email', function () {
    $invitationData = [
        'email' => 'duplicate@example.com',
        'name' => 'Duplicate User',
        'role_name' => 'employee',
        'expires_in_days' => 7,
    ];

    // Send first invitation
    $this->invitationService->sendInvitation($invitationData, $this->admin);

    // Attempt to send second invitation to same email
    expect(fn () => $this->invitationService->sendInvitation($invitationData, $this->admin))
        ->toThrow(\InvalidArgumentException::class);
});

it('allows user to accept invitation', function () {
    $invitation = UserInvite::factory()->create([
        'email' => 'acceptuser@example.com',
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $userData = [
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ];

    $user = $this->invitationService->acceptInvitation($token, $userData);

    expect($user)
        ->toBeInstanceOf(User::class)
        ->and($user->email)->toBe('acceptuser@example.com')
        ->and($invitation->fresh()->status)->toBe('accepted');
});

it('prevents accepting expired invitation', function () {
    $invitation = UserInvite::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->subDay(),
    ]);

    $token = $invitation->generateToken();

    $userData = [
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
    ];

    expect(fn () => $this->invitationService->acceptInvitation($token, $userData))
        ->toThrow(\InvalidArgumentException::class);
});

it('allows declining invitation', function () {
    $invitation = UserInvite::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $result = $this->invitationService->declineInvitation($token, 'Not interested');

    expect($result)
        ->toBeInstanceOf(UserInvite::class)
        ->and($result->status)->toBe('declined');
});

it('allows admin to view invitation management page', function () {
    $this->actingAs($this->admin);

    $response = $this->get(route('admin.invitations.index'));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page->component('admin/invitations/Index'));
});

it('allows admin to create new invitation via form', function () {
    $this->actingAs($this->admin);

    $response = $this->post(route('admin.invitations.store'), [
        'email' => 'formuser@example.com',
        'name' => 'Form User',
        'department_uuid' => $this->department->uuid,
        'role_name' => 'employee',
        'expires_in_days' => 14,
        'send_immediately' => true,
    ]);

    $response->assertRedirect(route('admin.invitations.index'));

    $this->assertDatabaseHas('user_invites', [
        'email' => 'formuser@example.com',
        'status' => 'pending',
    ]);
});

it('prevents non-admin from accessing invitation management', function () {
    $employee = User::factory()->create();
    $employee->assignRole('employee');

    $this->actingAs($employee);

    $response = $this->get(route('admin.invitations.index'));

    // Should redirect to login or dashboard due to lack of admin permissions
    $response->assertRedirect();
});

it('shows invitation acceptance page for valid token', function () {
    $invitation = UserInvite::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $response = $this->get(route('invitation.show', ['token' => $token]));

    $response->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('auth/invitation/Show')
            ->has('invitation.email')
            ->has('invitation.name')
        );
});

it('redirects invalid invitation tokens to login', function () {
    $response = $this->get(route('invitation.show', ['token' => 'invalid-token']));

    $response->assertRedirect(route('login'))
        ->assertSessionHasErrors(['token']);
});

it('allows user to accept invitation through web form', function () {
    // Create invitation directly instead of using factory
    $invitation = UserInvite::create([
        'invited_by' => $this->admin->id,
        'email' => 'webuser@example.com',
        'name' => 'Web User',
        'role_name' => 'employee',
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
        'token' => hash('sha256', 'test-token-123'),
        'invited_at' => now(),
        'invite_data' => [],
        'reminder_count' => 0,
    ]);

    $token = 'test-token-123'; // Use a simple token for testing

    // Debug: check if invitation exists and has required fields
    $foundInvitation = UserInvite::findByToken($token);
    expect($foundInvitation)->not->toBeNull();
    expect($foundInvitation->email)->toBe('webuser@example.com');
    expect($foundInvitation->name)->toBe('Web User');
    expect($foundInvitation->role_name)->toBe('employee');

    // Debug: Check users before
    $usersBefore = User::all();
    expect($usersBefore)->toHaveCount(1); // Only admin should exist

    $response = $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => true,
    ]);

    $response->assertRedirect(route('dashboard'));    // Debug: Check users after
    $usersAfter = User::all();
    expect($usersAfter)->toHaveCount(2); // Admin + new user

    $newUser = $usersAfter->where('email', '!=', $this->admin->email)->first();
    expect($newUser)->not->toBeNull();
    expect($newUser->email)->toBe('webuser@example.com');
    expect($newUser->name)->toBe('Web User');

    $this->assertDatabaseHas('users', [
        'email' => 'webuser@example.com',
        'name' => 'Web User',
    ]);

    $this->assertDatabaseHas('user_invites', [
        'email' => 'webuser@example.com',
        'status' => 'accepted',
    ]);
});

it('validates password requirements during invitation acceptance', function () {
    $invitation = UserInvite::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $response = $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'weak',
        'password_confirmation' => 'weak',
        'terms_accepted' => true,
    ]);

    $response->assertSessionHasErrors(['password']);
});

it('requires terms acceptance during invitation acceptance', function () {
    $invitation = UserInvite::factory()->create([
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $response = $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'password',
        'password_confirmation' => 'password',
        'terms_accepted' => false,
    ]);

    $response->assertSessionHasErrors(['terms_accepted']);
});

it('tracks invitation statistics correctly', function () {
    // Create various invitations by the admin user directly
    UserInvite::create([
        'invited_by' => $this->admin->id,
        'email' => 'pending@example.com',
        'name' => 'Pending User',
        'role_name' => 'employee',
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
        'token' => hash('sha256', bin2hex(random_bytes(32))),
        'invited_at' => now(),
        'invite_data' => [],
        'reminder_count' => 0,
    ]);

    UserInvite::create([
        'invited_by' => $this->admin->id,
        'email' => 'accepted@example.com',
        'name' => 'Accepted User',
        'role_name' => 'employee',
        'status' => 'accepted',
        'accepted_at' => now(),
        'expires_at' => now()->addWeek(),
        'token' => hash('sha256', bin2hex(random_bytes(32))),
        'invited_at' => now(),
        'invite_data' => [],
        'reminder_count' => 0,
    ]);

    UserInvite::create([
        'invited_by' => $this->admin->id,
        'email' => 'declined@example.com',
        'name' => 'Declined User',
        'role_name' => 'employee',
        'status' => 'declined',
        'declined_at' => now(),
        'expires_at' => now()->addWeek(),
        'token' => hash('sha256', bin2hex(random_bytes(32))),
        'invited_at' => now(),
        'invite_data' => [],
        'reminder_count' => 0,
    ]);

    UserInvite::create([
        'invited_by' => $this->admin->id,
        'email' => 'expired@example.com',
        'name' => 'Expired User',
        'role_name' => 'employee',
        'status' => 'pending',
        'expires_at' => now()->subDay(), // expired
        'token' => hash('sha256', bin2hex(random_bytes(32))),
        'invited_at' => now(),
        'invite_data' => [],
        'reminder_count' => 0,
    ]);

    $stats = $this->invitationService->getInvitationStats($this->admin);

    expect($stats)
        ->toHaveKey('total_sent', 4)
        ->toHaveKey('pending', 1)
        ->toHaveKey('accepted', 1)
        ->toHaveKey('declined', 1)
        ->toHaveKey('expired', 1);
});
