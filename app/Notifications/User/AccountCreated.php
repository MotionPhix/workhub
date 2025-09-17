<?php

namespace App\Notifications\User;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccountCreated extends Notification
{
    use Queueable;

    protected $user;

    protected $temporaryPassword;

    public function __construct(User $user, ?string $temporaryPassword = null)
    {
        $this->user = $user;
        $this->temporaryPassword = $temporaryPassword;
    }

    public function via($notifiable)
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable)
    {
        $mailMessage = (new MailMessage)
            ->subject('Welcome to '.config('app.name'))
            ->greeting("Hello {$this->user->name}!")
            ->line('Your account has been created successfully.')
            ->action('Login to Your Account', route('login'));

        if ($this->temporaryPassword) {
            $mailMessage->line("Temporary Password: {$this->temporaryPassword}")
                ->line('Please change your password after first login.');
        }

        return $mailMessage;
    }

    public function toDatabase($notifiable)
    {
        return [
            'message' => 'Your account has been created',
            'user_id' => $this->user->id,
        ];
    }
}
