<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WeeklyReportNotification extends Notification
{
    use Queueable;

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $entries = $notifiable->workEntries()
            ->whereBetween('work_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        return (new MailMessage)
            ->subject('Weekly Work Report')
            ->markdown('emails.weekly-report', [
                'entries' => $entries,
                'user' => $notifiable,
                'weekStart' => now()->startOfWeek(),
                'weekEnd' => now()->endOfWeek(),
            ]);
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'weekly_report',
            'message' => 'Weekly work report generated',
        ];
    }
}
