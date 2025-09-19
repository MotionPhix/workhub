<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class NotificationPreference extends Model
{
    use HasFactory;

    protected $fillable = [
        'uuid',
        'user_id',
        'task_assignments',
        'task_updates',
        'task_completions',
        'project_updates',
        'project_deadlines',
        'team_updates',
        'report_submissions',
        'report_approvals',
        'system_maintenance',
        'security_alerts',
        'email_notifications',
        'browser_notifications',
        'mobile_push_notifications',
        'quiet_hours',
        'digest_frequency',
        'weekend_notifications',
        'minimum_priority',
        'channel_preferences',
        'sound_enabled',
        'sound_preference',
        'vibration_enabled',
    ];

    protected $casts = [
        'task_assignments' => 'boolean',
        'task_updates' => 'boolean',
        'task_completions' => 'boolean',
        'project_updates' => 'boolean',
        'project_deadlines' => 'boolean',
        'team_updates' => 'boolean',
        'report_submissions' => 'boolean',
        'report_approvals' => 'boolean',
        'system_maintenance' => 'boolean',
        'security_alerts' => 'boolean',
        'email_notifications' => 'boolean',
        'browser_notifications' => 'boolean',
        'mobile_push_notifications' => 'boolean',
        'weekend_notifications' => 'boolean',
        'sound_enabled' => 'boolean',
        'vibration_enabled' => 'boolean',
        'quiet_hours' => 'array',
        'digest_frequency' => 'array',
        'channel_preferences' => 'array',
    ];

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($model) {
            if (!$model->uuid) {
                $model->uuid = Str::uuid()->toString();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function shouldReceiveNotification(string $type, string $priority = 'medium'): bool
    {
        // Check if notification type is enabled
        if (!$this->{$type}) {
            return false;
        }

        // Check priority threshold
        $priorities = ['low', 'medium', 'high', 'urgent'];
        $minPriorityIndex = array_search($this->minimum_priority, $priorities);
        $currentPriorityIndex = array_search($priority, $priorities);

        if ($currentPriorityIndex < $minPriorityIndex) {
            return false;
        }

        // Check quiet hours
        if ($this->isInQuietHours()) {
            return false;
        }

        // Check weekend notifications
        if (!$this->weekend_notifications && $this->isWeekend()) {
            return false;
        }

        return true;
    }

    public function getEnabledChannels(): array
    {
        $channels = [];

        if ($this->email_notifications) {
            $channels[] = 'mail';
        }

        if ($this->browser_notifications) {
            $channels[] = 'broadcast';
        }

        if ($this->mobile_push_notifications) {
            $channels[] = 'push';
        }

        return $channels;
    }

    private function isInQuietHours(): bool
    {
        if (!$this->quiet_hours) {
            return false;
        }

        $now = now();
        $start = $now->parse($this->quiet_hours['start'] ?? '22:00');
        $end = $now->parse($this->quiet_hours['end'] ?? '08:00');

        if ($start > $end) {
            // Quiet hours span midnight
            return $now >= $start || $now <= $end;
        }

        return $now >= $start && $now <= $end;
    }

    private function isWeekend(): bool
    {
        return now()->isWeekend();
    }
}
