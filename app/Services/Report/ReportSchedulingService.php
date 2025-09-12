<?php

namespace App\Services\Report;

use App\Jobs\GenerateScheduledReportJob;
use App\Jobs\SendReportReminderJob;
use App\Models\Report;
use App\Models\User;
use App\Models\UserReportSchedule;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReportSchedulingService
{
    public function createUserSchedule(User $user, array $scheduleData): UserReportSchedule
    {
        return DB::transaction(function () use ($user, $scheduleData) {
            $schedule = UserReportSchedule::create([
                'user_id' => $user->id,
                'report_type' => $scheduleData['report_type'],
                'frequency' => $scheduleData['frequency'],
                'day_of_week' => $scheduleData['day_of_week'] ?? null,
                'day_of_month' => $scheduleData['day_of_month'] ?? null,
                'send_time' => $scheduleData['send_time'],
                'timezone' => $scheduleData['timezone'] ?? $user->settings['timezone'] ?? 'UTC',
                'is_active' => $scheduleData['is_active'] ?? true,
                'auto_generate' => $scheduleData['auto_generate'] ?? true,
                'require_approval' => $scheduleData['require_approval'] ?? false,
                'recipient_emails' => $scheduleData['recipient_emails'] ?? [$user->manager_email],
                'reminder_settings' => $scheduleData['reminder_settings'] ?? [
                    'enabled' => true,
                    'hours_before' => 24,
                ],
                'template_preferences' => $scheduleData['template_preferences'] ?? [],
            ]);

            $schedule->updateNextDueDate();

            Log::info('Report schedule created', [
                'user_id' => $user->id,
                'schedule_id' => $schedule->id,
                'frequency' => $schedule->frequency,
                'next_due' => $schedule->next_due_at,
            ]);

            return $schedule;
        });
    }

    public function processScheduledReports(): array
    {
        $processed = [];
        $errors = [];

        $dueSchedules = UserReportSchedule::active()
            ->due()
            ->with('user')
            ->get();

        foreach ($dueSchedules as $schedule) {
            try {
                $this->processSchedule($schedule);
                $processed[] = $schedule->id;
            } catch (\Exception $e) {
                $errors[] = [
                    'schedule_id' => $schedule->id,
                    'user_id' => $schedule->user_id,
                    'error' => $e->getMessage(),
                ];

                Log::error('Failed to process scheduled report', [
                    'schedule_id' => $schedule->id,
                    'user_id' => $schedule->user_id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }

        return [
            'processed' => $processed,
            'errors' => $errors,
        ];
    }

    private function processSchedule(UserReportSchedule $schedule): void
    {
        if ($schedule->auto_generate) {
            // Generate report automatically
            GenerateScheduledReportJob::dispatch($schedule);
        } else {
            // Send reminder to create manual report
            SendReportReminderJob::dispatch($schedule);
        }

        $schedule->markAsSent();
    }

    public function sendReportReminders(): array
    {
        $reminders = [];
        $errors = [];

        $schedules = UserReportSchedule::active()
            ->where('next_due_at', '>', now())
            ->with('user')
            ->get();

        foreach ($schedules as $schedule) {
            try {
                if ($schedule->shouldSendReminder()) {
                    SendReportReminderJob::dispatch($schedule);

                    // Update last reminder sent time
                    $reminderSettings = $schedule->reminder_settings;
                    $reminderSettings['last_sent'] = now()->toISOString();
                    $schedule->update(['reminder_settings' => $reminderSettings]);

                    $reminders[] = $schedule->id;
                }
            } catch (\Exception $e) {
                $errors[] = [
                    'schedule_id' => $schedule->id,
                    'error' => $e->getMessage(),
                ];
            }
        }

        return [
            'reminders_sent' => $reminders,
            'errors' => $errors,
        ];
    }

    public function updateSchedule(UserReportSchedule $schedule, array $data): UserReportSchedule
    {
        return DB::transaction(function () use ($schedule, $data) {
            $schedule->update($data);

            // Recalculate next due date if timing changed
            if (array_intersect(array_keys($data), ['frequency', 'day_of_week', 'day_of_month', 'send_time', 'timezone'])) {
                $schedule->updateNextDueDate();
            }

            return $schedule->fresh();
        });
    }

    public function pauseSchedule(UserReportSchedule $schedule): void
    {
        $schedule->update(['is_active' => false]);

        Log::info('Report schedule paused', [
            'schedule_id' => $schedule->id,
            'user_id' => $schedule->user_id,
        ]);
    }

    public function resumeSchedule(UserReportSchedule $schedule): void
    {
        $schedule->update([
            'is_active' => true,
            'next_due_at' => $schedule->calculateNextDueDate(),
        ]);

        Log::info('Report schedule resumed', [
            'schedule_id' => $schedule->id,
            'user_id' => $schedule->user_id,
            'next_due' => $schedule->next_due_at,
        ]);
    }

    public function getUpcomingReports(User $user, int $days = 7): array
    {
        $schedules = UserReportSchedule::where('user_id', $user->id)
            ->active()
            ->where('next_due_at', '<=', now()->addDays($days))
            ->orderBy('next_due_at')
            ->get();

        return $schedules->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'type' => $schedule->report_type,
                'frequency' => $schedule->getFrequencyDisplay(),
                'due_at' => $schedule->next_due_at,
                'description' => $schedule->getScheduleDescription(),
                'auto_generate' => $schedule->auto_generate,
            ];
        })->toArray();
    }

    public function getUserScheduleStats(User $user): array
    {
        $schedules = UserReportSchedule::where('user_id', $user->id)->get();

        return [
            'total_schedules' => $schedules->count(),
            'active_schedules' => $schedules->where('is_active', true)->count(),
            'auto_generated' => $schedules->where('auto_generate', true)->count(),
            'manual_schedules' => $schedules->where('auto_generate', false)->count(),
            'next_due' => $schedules->where('is_active', true)
                ->sortBy('next_due_at')
                ->first()?->next_due_at,
        ];
    }
}
