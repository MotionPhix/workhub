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

            // Generate secure token first
            $rawToken = bin2hex(random_bytes(32));
            $hashedToken = hash('sha256', $rawToken);

            // Create the invitation
            $invite = UserInvite::create([
                'invited_by' => $inviter->id,
                'email' => strtolower(trim($inviteData['email'])),
                'name' => trim($inviteData['name']),
                'department_uuid' => $inviteData['department_uuid'] ?? null,
                'manager_email' => $inviteData['manager_email'] ?? null,
                'role_name' => $inviteData['role_name'] ?? 'employee',
                'token' => $hashedToken,
                'invited_at' => now(),
                'expires_at' => now()->addDays($inviteData['expires_in_days'] ?? 7),
                'status' => 'pending',
                'invite_data' => [
                    'welcome_message' => $inviteData['welcome_message'] ?? null,
                    'additional_permissions' => $inviteData['additional_permissions'] ?? [],
                    'initial_schedule_settings' => $inviteData['initial_schedule_settings'] ?? [],
                ],
            ]);

            // Use the raw token for the email
            $token = $rawToken;

            // Send invitation email
            // SendUserInviteJob::dispatch($invite, $token)->onQueue('emails');

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

    public function acceptInvitation(string $token, array $data): User
    {
        $invitation = UserInvite::findByToken($token);

        if (! $invitation) {
            throw new \Exception('Invalid or expired invitation token.');
        }

        if (! $invitation->canBeAccepted()) {
            throw new \Exception('This invitation cannot be accepted.');
        }

        return DB::transaction(function () use ($invitation, $data) {
            // Create user
            $user = $this->createUserFromInvitation($invitation, $data);

            // Mark invitation as accepted
            $invitation->accept();

            // Log the successful registration
            Log::info('User registered via invitation', [
                'user_id' => $user->id,
                'invite_id' => $invitation->id,
                'email' => $user->email,
                'role' => $invitation->role_name,
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
        // $invite->inviter->notify(new UserInvitedNotification(null, $invite, 'declined', $reason));

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
        // SendUserInviteJob::dispatch($invite, null, true)->onQueue('emails');

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

}
