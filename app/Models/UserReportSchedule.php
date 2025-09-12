<?php

namespace App\Models;

use App\Traits\BootableUuid;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserReportSchedule extends Model
{
    use BootableUuid, HasFactory;

    protected $fillable = [
        'user_id',
        'report_type',
        'frequency',
        'day_of_week',
        'day_of_month',
        'send_time',
        'timezone',
        'is_active',
        'auto_generate',
        'require_approval',
        'recipient_emails',
        'reminder_settings',
        'template_preferences',
        'last_sent_at',
        'next_due_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'auto_generate' => 'boolean',
        'require_approval' => 'boolean',
        'recipient_emails' => 'array',
        'reminder_settings' => 'array',
        'template_preferences' => 'array',
        'last_sent_at' => 'datetime',
        'next_due_at' => 'datetime',
        'send_time' => 'datetime:H:i',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeDue($query)
    {
        return $query->where('next_due_at', '<=', now());
    }

    public function scopeByFrequency($query, string $frequency)
    {
        return $query->where('frequency', $frequency);
    }

    // Methods
    public function calculateNextDueDate(): Carbon
    {
        $now = Carbon::now($this->timezone ?? 'UTC');
        $sendTime = Carbon::parse($this->send_time);

        return match ($this->frequency) {
            'daily' => $this->getNextDaily($now, $sendTime),
            'weekly' => $this->getNextWeekly($now, $sendTime),
            'bi_weekly' => $this->getNextBiWeekly($now, $sendTime),
            'monthly' => $this->getNextMonthly($now, $sendTime),
            'quarterly' => $this->getNextQuarterly($now, $sendTime),
            default => $now->addDay(),
        };
    }

    private function getNextDaily(Carbon $now, Carbon $sendTime): Carbon
    {
        $next = $now->copy()->setTime($sendTime->hour, $sendTime->minute);

        if ($next->isPast()) {
            $next->addDay();
        }

        return $next;
    }

    private function getNextWeekly(Carbon $now, Carbon $sendTime): Carbon
    {
        $targetDay = $this->day_of_week ?? Carbon::FRIDAY;
        $next = $now->copy()->next($targetDay)->setTime($sendTime->hour, $sendTime->minute);

        // If it's the same day but past the send time, move to next week
        if ($now->dayOfWeek === $targetDay && $now->gte($next)) {
            $next->addWeek();
        }

        return $next;
    }

    private function getNextBiWeekly(Carbon $now, Carbon $sendTime): Carbon
    {
        $next = $this->getNextWeekly($now, $sendTime);

        // If we sent last week, skip to the week after next
        if ($this->last_sent_at && $this->last_sent_at->isAfter($now->copy()->subWeek())) {
            $next->addWeek();
        }

        return $next;
    }

    private function getNextMonthly(Carbon $now, Carbon $sendTime): Carbon
    {
        $targetDay = $this->day_of_month ?? $now->endOfMonth()->day;

        $next = $now->copy()->day(min($targetDay, $now->daysInMonth))
            ->setTime($sendTime->hour, $sendTime->minute);

        if ($next->isPast()) {
            $next->addMonth()->day(min($targetDay, $next->daysInMonth));
        }

        return $next;
    }

    private function getNextQuarterly(Carbon $now, Carbon $sendTime): Carbon
    {
        $currentQuarter = ceil($now->month / 3);
        $nextQuarterMonth = ($currentQuarter * 3) + 1;

        if ($nextQuarterMonth > 12) {
            $nextQuarterMonth = 1;
            $year = $now->year + 1;
        } else {
            $year = $now->year;
        }

        $targetDay = $this->day_of_month ?? 1;

        return Carbon::create($year, $nextQuarterMonth, $targetDay)
            ->setTime($sendTime->hour, $sendTime->minute);
    }

    public function updateNextDueDate(): void
    {
        $this->update([
            'next_due_at' => $this->calculateNextDueDate(),
        ]);
    }

    public function markAsSent(): void
    {
        $this->update([
            'last_sent_at' => now(),
            'next_due_at' => $this->calculateNextDueDate(),
        ]);
    }

    public function shouldSendReminder(): bool
    {
        if (! $this->reminder_settings || ! $this->is_active) {
            return false;
        }

        $reminderHours = $this->reminder_settings['hours_before'] ?? 24;
        $reminderTime = $this->next_due_at->copy()->subHours($reminderHours);

        return now()->gte($reminderTime) &&
               (! $this->reminder_settings['last_sent'] ||
                Carbon::parse($this->reminder_settings['last_sent'])->diffInHours() >= 12);
    }

    public function getFrequencyDisplay(): string
    {
        return match ($this->frequency) {
            'daily' => 'Daily',
            'weekly' => 'Weekly',
            'bi_weekly' => 'Bi-weekly',
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            default => 'Custom',
        };
    }

    public function getScheduleDescription(): string
    {
        $time = Carbon::parse($this->send_time)->format('g:i A');

        return match ($this->frequency) {
            'daily' => "Daily at {$time}",
            'weekly' => 'Every '.Carbon::create()->dayOfWeek($this->day_of_week)->format('l')." at {$time}",
            'bi_weekly' => 'Every other '.Carbon::create()->dayOfWeek($this->day_of_week)->format('l')." at {$time}",
            'monthly' => "Monthly on day {$this->day_of_month} at {$time}",
            'quarterly' => "Quarterly on day {$this->day_of_month} at {$time}",
            default => 'Custom schedule',
        };
    }
}
