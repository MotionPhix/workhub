<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordResetNotification extends Notification
{
  use Queueable;

  public function via($notifiable)
  {
    return ['mail', 'database'];
  }

  public function toMail($notifiable)
  {
    return (new MailMessage)
      ->subject('Password Reset Confirmation')
      ->line('Your account password has been reset.')
      ->line('If you did not make this change, please contact support immediately.')
      ->action('Login', route('login'))
      ->line('This is an automated security notification.');
  }

  public function toDatabase($notifiable)
  {
    return [
      'message' => 'Your account password was reset',
      'action_url' => route('login')
    ];
  }
}
