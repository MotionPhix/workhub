<?php

namespace App\Notifications;

use App\Events\NotificationRequested;
use App\Events\NotificationSent;
use App\Models\WorkEntry;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkEntryNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public WorkEntry $workEntry,
        public string $type,
        public string $message,
    ) {}

    public function via(object $notifiable): array
    {
        $preferences = $notifiable->notificationPreferences;

        if (!$preferences || !$preferences->shouldReceiveNotification('task_updates')) {
            return [];
        }

        return $preferences->getEnabledChannels();
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Work Entry {$this->type}: {$this->workEntry->work_title}")
            ->greeting("Hello {$notifiable->name},")
            ->line($this->message)
            ->action('View Work Entry', route('work-entries.show', $this->workEntry->uuid))
            ->line('Thank you for using WorkHub!');
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        // Fire the Verbs event for notification requested
        NotificationRequested::fire(
            userId: $notifiable->id,
            type: 'work_entry_' . strtolower($this->type),
            title: "Work Entry {$this->type}",
            message: $this->message,
            data: [
                'work_entry_id' => $this->workEntry->uuid,
                'work_title' => $this->workEntry->work_title,
                'status' => $this->workEntry->status,
            ],
            priority: 'medium',
            channels: ['browser'],
            actionUrl: route('work-entries.show', $this->workEntry->uuid),
            actionText: 'View Work Entry'
        );

        return new BroadcastMessage([
            'id' => $this->id,
            'type' => 'work_entry_' . strtolower($this->type),
            'title' => "Work Entry {$this->type}",
            'message' => $this->message,
            'data' => [
                'work_entry_id' => $this->workEntry->uuid,
                'work_title' => $this->workEntry->work_title,
                'status' => $this->workEntry->status,
            ],
            'action_url' => route('work-entries.show', $this->workEntry->uuid),
            'action_text' => 'View Work Entry',
            'created_at' => now()->toISOString(),
        ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'work_entry_' . strtolower($this->type),
            'title' => "Work Entry {$this->type}",
            'message' => $this->message,
            'data' => [
                'work_entry_id' => $this->workEntry->uuid,
                'work_title' => $this->workEntry->work_title,
                'status' => $this->workEntry->status,
            ],
            'action_url' => route('work-entries.show', $this->workEntry->uuid),
            'action_text' => 'View Work Entry',
        ];
    }

    public function broadcastOn(): array
    {
        return ["user.{$this->notifiable->id}"];
    }

    public function broadcastAs(): string
    {
        return 'notification.received';
    }
}
