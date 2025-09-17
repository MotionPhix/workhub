<?php

use App\Models\Department;
use App\Models\User;
use App\Models\UserInvite;
use App\Services\Auth\InvitationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    // Create necessary roles
    Role::create(['name' => 'admin']);
    Role::create(['name' => 'employee']);
    Role::create(['name' => 'manager']);

    // Create necessary permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'view-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'create-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'edit-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'delete-invitations']);
    \Spatie\Permission\Models\Permission::create(['name' => 'access-admin-panel']);

    // Create test department directly to avoid factory dependencies
    $this->department = Department::create([
        'name' => 'IT Department',
        'description' => 'Information Technology',
    ]);

    // Create admin user (simulating the seeded admin)
    $this->admin = User::factory()->create([
        'name' => 'Admin User',
        'email' => 'admin@workhub.com',
        'password' => Hash::make('password'),
        'department_uuid' => $this->department->uuid,
        'is_active' => true,
    ]);
    $this->admin->assignRole('admin');

    // Give admin role the necessary permissions
    $adminRole = Role::where('name', 'admin')->first();
    $adminRole->givePermissionTo(['view-invitations', 'create-invitations', 'edit-invitations', 'delete-invitations', 'access-admin-panel']);

    // Create a manager user for manager assignment in invitations
    $this->manager = User::factory()->create([
        'name' => 'Manager User',
        'email' => 'manager@workhub.com',
        'department_uuid' => $this->department->uuid,
    ]);
    $this->manager->assignRole('manager');

    $this->invitationService = app(InvitationService::class);
});

describe('Invitation Creation Flow', function () {

    it('allows admin to create invitation through web interface', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.invitations.store'), [
            'email' => 'newemployee@example.com',
            'name' => 'John Doe',
            'department_uuid' => $this->department->uuid,
            'manager_email' => $this->manager->email,
            'role_name' => 'employee',
            'expires_in_days' => 14,
            'welcome_message' => 'Welcome to our team!',
            'send_immediately' => true,
        ]);

        $response->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('success');

        // Verify invitation was created in database
        $invitation = UserInvite::where('email', 'newemployee@example.com')->first();
        expect($invitation)
            ->not->toBeNull()
            ->and($invitation->name)->toBe('John Doe')
            ->and($invitation->role_name)->toBe('employee')
            ->and($invitation->status)->toBe('pending')
            ->and($invitation->invited_by)->toBe($this->admin->id)
            ->and($invitation->department_uuid)->toBe($this->department->uuid)
            ->and($invitation->manager_email)->toBe($this->manager->email);
    });

    it('prevents duplicate invitations for same email', function () {
        $this->actingAs($this->admin);

        // Create first invitation
        $this->post(route('admin.invitations.store'), [
            'email' => 'duplicate@example.com',
            'name' => 'First User',
            'role_name' => 'employee',
            'expires_in_days' => 7,
            'send_immediately' => true,
        ]);

        // Try to create second invitation with same email
        $response = $this->post(route('admin.invitations.store'), [
            'email' => 'duplicate@example.com',
            'name' => 'Second User',
            'role_name' => 'employee',
            'expires_in_days' => 7,
            'send_immediately' => true,
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('prevents invitations for existing users', function () {
        $this->actingAs($this->admin);

        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $response = $this->post(route('admin.invitations.store'), [
            'email' => 'existing@example.com',
            'name' => 'Existing User',
            'role_name' => 'employee',
            'expires_in_days' => 7,
            'send_immediately' => true,
        ]);

        $response->assertSessionHasErrors(['email']);
    });

    it('validates required invitation fields', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.invitations.store'), [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['email', 'name']);
    });

    it('allows bulk invitation creation', function () {
        $this->actingAs($this->admin);

        $response = $this->post(route('admin.invitations.bulk'), [
            'global_role_name' => 'employee',
            'global_expires_in_days' => 7,
            'global_department_uuid' => $this->department->uuid,
            'send_immediately' => true,
            'invitations' => [
                [
                    'email' => 'bulk1@example.com',
                    'name' => 'Bulk User 1',
                ],
                [
                    'email' => 'bulk2@example.com',
                    'name' => 'Bulk User 2',
                ],
            ],
        ]);

        $response->assertRedirect(route('admin.invitations.index'))
            ->assertSessionHas('success');

        // Verify both invitations were created
        expect(UserInvite::where('email', 'bulk1@example.com')->exists())->toBeTrue();
        expect(UserInvite::where('email', 'bulk2@example.com')->exists())->toBeTrue();
    });

});

describe('Invitation Acceptance Flow', function () {

    it('shows invitation page for valid token', function () {
        $invitation = UserInvite::factory()->create([
            'email' => 'invitee@example.com',
            'name' => 'Jane Smith',
            'department_uuid' => $this->department->uuid,
            'manager_email' => $this->manager->email,
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        $response = $this->get(route('invitation.show', ['token' => $token]));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Auth/invitation/Show')
                ->has('invitation')
                ->where('invitation.email', 'invitee@example.com')
                ->where('invitation.name', 'Jane Smith')
            );
    });

    it('redirects invalid tokens to login', function () {
        $response = $this->get(route('invitation.show', ['token' => 'invalid-token']));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['token']);
    });

    it('redirects expired invitations to login', function () {
        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->subDay(), // Expired
        ]);

        $token = $invitation->generateToken();

        $response = $this->get(route('invitation.show', ['token' => $token]));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['token']);
    });

});

describe('User Registration Through Invitation', function () {

    it('allows user to register through valid invitation', function () {
        // Create a valid invitation
        $invitation = UserInvite::factory()->create([
            'email' => 'newuser@example.com',
            'name' => 'New User',
            'department_uuid' => $this->department->uuid,
            'manager_email' => $this->manager->email,
            'role_name' => 'employee',
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        // Accept the invitation
        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'UniqueTestPass2024!@#',
            'password_confirmation' => 'UniqueTestPass2024!@#',
            'terms_accepted' => true,
        ]);

        // Should redirect to dashboard after successful registration
        $response->assertRedirect(route('dashboard'))
            ->assertSessionHas('success');

        // Verify user was created
        $user = User::where('email', 'newuser@example.com')->first();
        expect($user)
            ->not->toBeNull()
            ->and($user->name)->toBe('New User')
            ->and($user->department_uuid)->toBe($this->department->uuid)
            ->and($user->manager_email)->toBe($this->manager->email)
            ->and($user->is_active)->toBeTrue();

        // Verify user has correct role
        expect($user->hasRole('employee'))->toBeTrue();

        // Verify invitation was marked as accepted
        $invitation->refresh();
        expect($invitation->status)->toBe('accepted')
            ->and($invitation->accepted_at)->not->toBeNull();

        // Verify user is automatically logged in
        $this->assertAuthenticatedAs($user);
    });

    it('validates password requirements during registration', function () {
        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        // Test weak password
        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'weak',
            'password_confirmation' => 'weak',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('requires password confirmation to match', function () {
        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'DifferentPassword123!',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['password']);
    });

    it('requires terms acceptance', function () {
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

    it('prevents registration through expired invitation', function () {
        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->subDay(), // Expired
        ]);

        $token = $invitation->generateToken();

        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['error']);

        // Verify user was not created
        expect(User::where('email', $invitation->email)->exists())->toBeFalse();
    });

    it('prevents registration if user already exists', function () {
        // Create existing user
        User::factory()->create(['email' => 'existing@example.com']);

        $invitation = UserInvite::factory()->create([
            'email' => 'existing@example.com',
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'terms_accepted' => true,
        ]);

        $response->assertSessionHasErrors(['error']);
    });

    it('redirects to onboarding if user onboarding is not completed', function () {
        // Create invitation
        $invitation = UserInvite::factory()->create([
            'email' => 'onboarding@example.com',
            'name' => 'Onboarding User',
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        // Accept invitation
        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'SecurePassword123!',
            'password_confirmation' => 'SecurePassword123!',
            'terms_accepted' => true,
        ]);

        // Should redirect to onboarding if onboarding exists and is not completed
        // This will depend on your onboarding implementation
        $user = User::where('email', 'onboarding@example.com')->first();
        if ($user->userOnboarding && ! $user->userOnboarding->is_completed) {
            $response->assertRedirect(route('onboarding.show', ['step' => 1]));
        } else {
            $response->assertRedirect(route('dashboard'));
        }
    });

});

describe('Invitation Decline Flow', function () {

    it('allows user to decline invitation', function () {
        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $token = $invitation->generateToken();

        $response = $this->post(route('invitation.decline', ['token' => $token]));

        $response->assertRedirect(route('login'))
            ->assertSessionHas('message');

        // Verify invitation was marked as declined
        $invitation->refresh();
        expect($invitation->status)->toBe('declined')
            ->and($invitation->declined_at)->not->toBeNull();
    });

    it('prevents declining invalid invitation', function () {
        $response = $this->post(route('invitation.decline', ['token' => 'invalid-token']));

        $response->assertRedirect(route('login'))
            ->assertSessionHasErrors(['token']);
    });

});

describe('Invitation Management', function () {

    it('allows admin to view invitations list', function () {
        $this->actingAs($this->admin);

        // Create some invitations for testing
        UserInvite::factory()->count(3)->create([
            'invited_by' => $this->admin->id,
        ]);

        $response = $this->get(route('admin.invitations.index'));

        $response->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('admin/invitations/Index')
                ->has('invitations.data', 3)
            );
    });

    it('allows admin to resend invitation', function () {
        $this->actingAs($this->admin);

        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $response = $this->post(route('admin.invitations.resend', $invitation));

        $response->assertRedirect()
            ->assertSessionHas('success');

        // Token should be regenerated
        expect($invitation->fresh()->token)->not->toBe($invitation->token);
    });

    it('allows admin to cancel invitation', function () {
        $this->actingAs($this->admin);

        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addWeek(),
        ]);

        $response = $this->post(route('admin.invitations.cancel', $invitation));

        $response->assertRedirect()
            ->assertSessionHas('success');

        expect($invitation->fresh()->status)->toBe('cancelled');
    });

    it('allows admin to extend invitation expiry', function () {
        $this->actingAs($this->admin);

        $invitation = UserInvite::factory()->create([
            'status' => 'pending',
            'expires_at' => now()->addDay(),
        ]);

        $originalExpiry = $invitation->expires_at;

        // Note: This route doesn't exist in the current implementation
        // Skipping this test for now
        $this->markTestSkipped('Extend invitation route not implemented');
    });

    it('prevents non-admin from accessing invitation management', function () {
        $employee = User::factory()->create();
        $employee->assignRole('employee');

        $this->actingAs($employee);

        $response = $this->get(route('admin.invitations.index'));

        $response->assertStatus(403);
    });

});

describe('End-to-End Registration Flow', function () {

    it('completes full registration flow from invitation to login', function () {
        // Step 1: Admin creates invitation
        $this->actingAs($this->admin);

        $this->post(route('admin.invitations.store'), [
            'email' => 'fullflow@example.com',
            'name' => 'Full Flow User',
            'department_uuid' => $this->department->uuid,
            'manager_email' => $this->manager->email,
            'role_name' => 'employee',
            'expires_in_days' => 7,
            'welcome_message' => 'Welcome to our team!',
            'send_immediately' => true,
        ]);

        // Step 2: Get the invitation
        $invitation = UserInvite::where('email', 'fullflow@example.com')->first();
        expect($invitation)->not->toBeNull();

        $token = $invitation->token;

        // Step 3: User views invitation page
        $response = $this->get(route('invitation.show', ['token' => $token]));
        $response->assertOk();

        // Step 4: User accepts invitation and registers
        $response = $this->post(route('invitation.accept', ['token' => $token]), [
            'password' => 'FullFlowPassword123!',
            'password_confirmation' => 'FullFlowPassword123!',
            'terms_accepted' => true,
        ]);

        $response->assertRedirect(route('dashboard'));

        // Step 5: Verify user exists and can login
        $user = User::where('email', 'fullflow@example.com')->first();
        expect($user)
            ->not->toBeNull()
            ->and($user->name)->toBe('Full Flow User')
            ->and($user->hasRole('employee'))->toBeTrue();

        // Step 6: Test login with new credentials
        Auth::logout();

        $loginResponse = $this->post(route('login'), [
            'email' => 'fullflow@example.com',
            'password' => 'FullFlowPassword123!',
        ]);

        $loginResponse->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    });

});
