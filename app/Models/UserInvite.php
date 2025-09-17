<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserInvite extends Model
{
    use BootableUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'invited_by',
        'email',
        'name',
        'department_uuid',
        'manager_email',
        'role_name',
        'token',
        'invited_at',
        'expires_at',
        'accepted_at',
        'declined_at',
        'reminder_sent_at',
        'reminder_count',
        'invite_data',
        'status',
    ];

    protected $casts = [
        'invited_at' => 'datetime',
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'declined_at' => 'datetime',
        'reminder_sent_at' => 'datetime',
        'invite_data' => 'array',
    ];

    // Relationships
    public function inviter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'invited_by');
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class, 'department_uuid', 'uuid');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_email', 'email');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('status', 'pending')
            ->where('expires_at', '<=', now());
    }

    public function scopeAccepted($query)
    {
        return $query->where('status', 'accepted');
    }

    public function scopeDeclined($query)
    {
        return $query->where('status', 'declined');
    }

    // Methods
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && ! $this->isExpired();
    }

    public function canBeAccepted(): bool
    {
        return $this->isPending() && ! User::where('email', $this->email)->exists();
    }

    public function accept(): void
    {
        $this->update([
            'status' => 'accepted',
            'accepted_at' => now(),
        ]);
    }

    public function decline(): void
    {
        $this->update([
            'status' => 'declined',
            'declined_at' => now(),
        ]);
    }

    public function extend(int $days = 7): void
    {
        $this->update([
            'expires_at' => now()->addDays($days),
        ]);
    }

    public function sendReminder(): void
    {
        $this->update([
            'reminder_sent_at' => now(),
            'reminder_count' => $this->reminder_count + 1,
        ]);
    }

    public function generateToken(): string
    {
        $token = bin2hex(random_bytes(32));
        $this->update(['token' => hash('sha256', $token)]);

        return $token;
    }

    public static function findByToken(string $token): ?self
    {
        $hashedToken = hash('sha256', $token);

        return static::where('token', $hashedToken)
            ->pending()
            ->first();
    }

    public function getDaysUntilExpiry(): int
    {
        return now()->diffInDays($this->expires_at, false);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => $this->isExpired() ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800',
            'accepted' => 'bg-green-100 text-green-800',
            'declined' => 'bg-gray-100 text-gray-800',
            'cancelled' => 'bg-red-100 text-red-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getStatusDisplay(): string
    {
        return match ($this->status) {
            'pending' => $this->isExpired() ? 'Expired' : 'Pending',
            'accepted' => 'Accepted',
            'declined' => 'Declined',
            'cancelled' => 'Cancelled',
            default => 'Unknown',
        };
    }

    public function canSendReminder(): bool
    {
        if (! $this->isPending()) {
            return false;
        }

        // Don't send more than 3 reminders
        if ($this->reminder_count >= 3) {
            return false;
        }

        // Only send reminders if last one was more than 24 hours ago
        if ($this->reminder_sent_at && $this->reminder_sent_at->gt(now()->subDay())) {
            return false;
        }

        return true;
    }

    public function getInviteUrl(): string
    {
        return route('invitation.show', ['token' => $this->token]);
    }

    public function toArray()
    {
        $data = parent::toArray();

        // Add computed attributes
        $data['is_expired'] = $this->isExpired();
        $data['can_be_accepted'] = $this->canBeAccepted();
        $data['days_until_expiry'] = $this->getDaysUntilExpiry();
        $data['status_display'] = $this->getStatusDisplay();
        $data['invite_url'] = $this->getInviteUrl();

        return $data;
    }
}
