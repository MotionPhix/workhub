<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Invitation extends Model
{
    use BootableUuid, HasFactory, SoftDeletes;

    protected $fillable = [
        'email',
        'token',
        'invited_by',
        'role',
        'department_uuid',
        'manager_email',
        'job_title',
        'expires_at',
        'accepted_at',
        'user_id',
        'metadata',
        'status',
        'invitation_message',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'accepted_at' => 'datetime',
        'metadata' => 'array',
    ];

    protected $hidden = [
        'token',
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

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending')
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now());
    }

    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now())
            ->whereNull('accepted_at');
    }

    public function scopeAccepted($query)
    {
        return $query->whereNotNull('accepted_at')
            ->where('status', 'accepted');
    }

    // Methods
    public static function createInvitation(array $data): self
    {
        return self::create([
            'email' => $data['email'],
            'token' => Str::random(64),
            'invited_by' => $data['invited_by'],
            'role' => $data['role'] ?? 'employee',
            'department_uuid' => $data['department_uuid'] ?? null,
            'manager_email' => $data['manager_email'] ?? null,
            'job_title' => $data['job_title'] ?? null,
            'expires_at' => now()->addDays($data['expires_in_days'] ?? 7),
            'status' => 'pending',
            'invitation_message' => $data['invitation_message'] ?? null,
            'metadata' => $data['metadata'] ?? [],
        ]);
    }

    public function isValid(): bool
    {
        return $this->status === 'pending'
            && is_null($this->accepted_at)
            && $this->expires_at->isFuture();
    }

    public function isExpired(): bool
    {
        return $this->expires_at->isPast() || $this->status === 'expired';
    }

    public function isAccepted(): bool
    {
        return ! is_null($this->accepted_at) && $this->status === 'accepted';
    }

    public function markAsAccepted(User $user): void
    {
        $this->update([
            'accepted_at' => now(),
            'status' => 'accepted',
            'user_id' => $user->id,
        ]);
    }

    public function markAsExpired(): void
    {
        $this->update(['status' => 'expired']);
    }

    public function markAsCancelled(): void
    {
        $this->update(['status' => 'cancelled']);
    }

    public function resend(int $expiryDays = 7): void
    {
        $this->update([
            'token' => Str::random(64),
            'expires_at' => now()->addDays($expiryDays),
            'status' => 'pending',
        ]);
    }

    public function getStatusBadgeClass(): string
    {
        return match ($this->status) {
            'pending' => 'bg-yellow-100 text-yellow-800',
            'accepted' => 'bg-green-100 text-green-800',
            'expired' => 'bg-red-100 text-red-800',
            'cancelled' => 'bg-gray-100 text-gray-800',
            default => 'bg-gray-100 text-gray-800',
        };
    }

    public function getDaysUntilExpiry(): int
    {
        return now()->diffInDays($this->expires_at, false);
    }

    public function getAcceptUrl(): string
    {
        return route('invitations.accept', ['token' => $this->token]);
    }

    public function toNotificationArray(): array
    {
        return [
            'email' => $this->email,
            'role' => $this->role,
            'department' => $this->department?->name,
            'job_title' => $this->job_title,
            'inviter_name' => $this->inviter->name,
            'expires_at' => $this->expires_at,
            'accept_url' => $this->getAcceptUrl(),
            'invitation_message' => $this->invitation_message,
        ];
    }

    // Boot method to handle token generation
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($invitation) {
            if (empty($invitation->token)) {
                $invitation->token = Str::random(64);
            }
        });
    }
}
