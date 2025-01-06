<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AdvancedWorkReminderNotification extends Notification
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
    /*return (new MailMessage)
      ->subject('Daily Work Entry Reminder')
      ->line('You haven\'t logged your work for today.')
      ->action('Log Work Entry', route('work-entries.create'))
      ->line('Don\'t forget to track your daily progress!');*/

    return (new MailMessage)
      ->subject('Daily Work Entry Reminder')
      ->markdown('emails.work-reminder', [
        'user' => $notifiable,
      ]);
  }

  public function toDatabase($notifiable)
  {
    return [
      'type' => 'work_reminder',
      'message' => 'Reminder to log your daily work',
      'action_url' => route('work-entries.create')
    ];
  }
}
