<?php

namespace App\Services\Auth;

use App\Jobs\SendUserInviteJob;
use App\Models\User;
use App\Models\UserInvite;
use App\Notifications\User\UserInvitedNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class InvitationService
{
    public function sendInvitation(array $inviteData, User $inviter): UserInvite
    {
        $this->validateInviteData($inviteData);

        return DB::transaction(function () use ($inviteData, $inviter) {
            // Check if user already exists
            if (User::where('email', $inviteData['email'])->exists()) {
                throw new \InvalidArgumentException('A user with this email already exists.');
            }

            // Check if there's already a pending invite
            $existingInvite = UserInvite::where('email', $inviteData['email'])
                ->pending()
                ->first();

            if ($existingInvite) {
                throw new \InvalidArgumentException('A pending invitation already exists for this email.');
            }

            // Create the invitation
            $invite = UserInvite::create([
                'invited_by' => $inviter->id,
                'email' => strtolower(trim($inviteData['email'])),
                'name' => trim($inviteData['name']),
                'department_uuid' => $inviteData['department_uuid'] ?? null,
                'manager_email' => $inviteData['manager_email'] ?? null,
                'job_title' => $inviteData['job_title'] ?? null,
                'role_name' => $inviteData['role_name'] ?? 'employee',
                'invited_at' => now(),
                'expires_at' => now()->addDays($inviteData['expires_in_days'] ?? 7),
                'status' => 'pending',
                'invite_data' => [
                    'welcome_message' => $inviteData['welcome_message'] ?? null,
                    'additional_permissions' => $inviteData['additional_permissions'] ?? [],
                    'initial_schedule_settings' => $inviteData['initial_schedule_settings'] ?? [],
                ],
            ]);

            // Generate secure token
            $token = $invite->generateToken();

            // Send invitation email
            SendUserInviteJob::dispatch($invite, $token)->onQueue('emails');

            // Log the invitation
            Log::info('User invitation sent', [
                'invite_id' => $invite->id,
                'invited_by' => $inviter->id,
                'inviter_email' => $inviter->email,
                'invitee_email' => $invite->email,
                'role' => $invite->role_name,
                'department' => $invite->department_uuid,
            ]);

            return $invite;
        });
    }

    public function acceptInvitation(string $token, array $userData): User
    {
        $invite = UserInvite::findByToken($token);

        if (! $invite) {
            throw new \InvalidArgumentException('Invalid or expired invitation token.');
        }

        if (! $invite->canBeAccepted()) {
            throw new \InvalidArgumentException('This invitation cannot be accepted. It may have expired or the user already exists.');
        }

        $this->validateUserData($userData);

        return DB::transaction(function () use ($invite, $userData) {
            // Create the user account
            $user = User::create([
                'name' => $invite->name,
                'email' => $invite->email,
                'password' => bcrypt($userData['password']),
                'department_uuid' => $invite->department_uuid,
                'manager_email' => $invite->manager_email,
                'job_title' => $invite->job_title,
                'gender' => $userData['gender'] ?? null,
                'is_active' => true,
                'joined_at' => now(),
                'settings' => array_merge([
                    'notifications' => [
                        'email' => true,
                        'sms' => false,
                    ],
                    'timezone' => $userData['timezone'] ?? 'UTC',
                ], $invite->invite_data['initial_schedule_settings'] ?? []),
            ]);

            // Assign role
            if ($invite->role_name) {
                $user->assignRole($invite->role_name);
            }

            // Mark invitation as accepted
            $invite->accept();

            // Send welcome notification to inviter
            $invite->inviter->notify(new UserInvitedNotification($user, $invite, 'accepted'));

            // Log successful registration
            Log::info('User registered via invitation', [
                'user_id' => $user->id,
                'invite_id' => $invite->id,
                'email' => $user->email,
                'role' => $invite->role_name,
            ]);

            return $user;
        });
    }

    public function declineInvitation(string $token, ?string $reason = null): UserInvite
    {
        $invite = UserInvite::findByToken($token);

        if (! $invite || ! $invite->isPending()) {
            throw new \InvalidArgumentException('Invalid or expired invitation token.');
        }

        $invite->decline();

        // Update invite data with decline reason
        if ($reason) {
            $inviteData = $invite->invite_data ?? [];
            $inviteData['decline_reason'] = $reason;
            $invite->update(['invite_data' => $inviteData]);
        }

        // Notify inviter
        $invite->inviter->notify(new UserInvitedNotification(null, $invite, 'declined', $reason));

        Log::info('Invitation declined', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'reason' => $reason,
        ]);

        return $invite;
    }

    public function sendReminder(UserInvite $invite): bool
    {
        if (! $invite->canSendReminder()) {
            return false;
        }

        // Send reminder email
        SendUserInviteJob::dispatch($invite, null, true)->onQueue('emails');

        $invite->sendReminder();

        Log::info('Invitation reminder sent', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'reminder_count' => $invite->reminder_count,
        ]);

        return true;
    }

    public function cancelInvitation(UserInvite $invite, User $canceller): bool
    {
        if (! $invite->isPending()) {
            return false;
        }

        $invite->update(['status' => 'cancelled']);

        Log::info('Invitation cancelled', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'cancelled_by' => $canceller->id,
        ]);

        return true;
    }

    public function extendInvitation(UserInvite $invite, int $days = 7): bool
    {
        if (! $invite->isPending()) {
            return false;
        }

        $invite->extend($days);

        Log::info('Invitation extended', [
            'invite_id' => $invite->id,
            'email' => $invite->email,
            'new_expiry' => $invite->expires_at,
            'extended_by_days' => $days,
        ]);

        return true;
    }

    public function bulkInvite(array $invites, User $inviter): array
    {
        $results = [
            'success' => [],
            'failed' => [],
        ];

        foreach ($invites as $inviteData) {
            try {
                $invite = $this->sendInvitation($inviteData, $inviter);
                $results['success'][] = [
                    'email' => $invite->email,
                    'invite_id' => $invite->id,
                ];
            } catch (\Exception $e) {
                $results['failed'][] = [
                    'email' => $inviteData['email'] ?? 'unknown',
                    'error' => $e->getMessage(),
                ];
            }
        }

        return $results;
    }

    public function getInvitationStats(?User $user = null): array
    {
        $query = UserInvite::query();

        if ($user) {
            $query->where('invited_by', $user->id);
        }

        $total = $query->count();
        $pending = $query->clone()->where('status', 'pending')->count();
        $accepted = $query->clone()->where('status', 'accepted')->count();
        $declined = $query->clone()->where('status', 'declined')->count();
        $expired = $query->clone()->expired()->count();

        return [
            'total_sent' => $total,
            'pending' => $pending,
            'accepted' => $accepted,
            'declined' => $declined,
            'expired' => $expired,
            'acceptance_rate' => $total > 0 ? round(($accepted / $total) * 100, 1) : 0,
        ];
    }

    public function cleanupExpiredInvitations(int $daysOld = 30): int
    {
        return UserInvite::where('status', 'pending')
            ->where('expires_at', '<', now()->subDays($daysOld))
            ->delete();
    }

    private function validateInviteData(array $data): void
    {
        $validator = Validator::make($data, [
            'email' => 'required|email|max:255|unique:users,email',
            'name' => 'required|string|max:255',
            'department_uuid' => 'nullable|exists:departments,uuid',
            'manager_email' => 'nullable|email|exists:users,email',
            'job_title' => 'nullable|string|max:255',
            'role_name' => 'nullable|string|exists:roles,name',
            'expires_in_days' => 'nullable|integer|min:1|max:30',
            'welcome_message' => 'nullable|string|max:500',
            'additional_permissions' => 'nullable|array',
            'initial_schedule_settings' => 'nullable|array',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }

    private function validateUserData(array $data): void
    {
        $validator = Validator::make($data, [
            'password' => 'required|string|min:8|confirmed',
            'password_confirmation' => 'required',
            'gender' => 'nullable|in:male,female,other',
            'timezone' => 'nullable|string|max:50',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }
    }
}
