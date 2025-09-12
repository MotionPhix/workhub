<?php

use App\Models\User;
use App\Models\UserInvite;
use App\Models\Department;
use App\Services\Auth\InvitationService;
use Spatie\Permission\Models\Role;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);
    
    // Create test admin user
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
    
    // Create test department
    $this->department = Department::factory()->create();
    
    // Create invitation service
    $this->invitationService = app(InvitationService::class);
});

it('allows admin to send invitation', function () {
    $invitationData = [
        'email' => 'newuser@example.com',
        'name' => 'New User',
        'job_title' => 'Developer',
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
        'job_title' => 'Developer',
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
        'job_title' => 'Designer',
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

    $response->assertStatus(403);
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
            ->component('auth/invitation/show')
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
    $invitation = UserInvite::factory()->create([
        'email' => 'webuser@example.com',
        'status' => 'pending',
        'expires_at' => now()->addWeek(),
    ]);

    $token = $invitation->generateToken();

    $response = $this->post(route('invitation.accept', ['token' => $token]), [
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'terms_accepted' => true,
    ]);

    $response->assertRedirect(route('dashboard'));
    
    $this->assertDatabaseHas('users', [
        'email' => 'webuser@example.com',
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
        'password' => 'SecurePassword123!',
        'password_confirmation' => 'SecurePassword123!',
        'terms_accepted' => false,
    ]);

    $response->assertSessionHasErrors(['terms_accepted']);
});

it('tracks invitation statistics correctly', function () {
    // Create various invitations
    UserInvite::factory()->create(['status' => 'pending', 'expires_at' => now()->addWeek()]);
    UserInvite::factory()->create(['status' => 'accepted']);
    UserInvite::factory()->create(['status' => 'declined']);
    UserInvite::factory()->create(['status' => 'pending', 'expires_at' => now()->subDay()]); // expired

    $stats = $this->invitationService->getInvitationStats();

    expect($stats)
        ->toHaveKey('total', 4)
        ->toHaveKey('pending', 1)
        ->toHaveKey('accepted', 1)
        ->toHaveKey('declined', 1)
        ->toHaveKey('expired', 1);
});
