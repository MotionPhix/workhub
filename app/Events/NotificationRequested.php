<?php

namespace App\Events;

use App\States\NotificationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class NotificationRequested extends Event
{
    #[StateId(NotificationState::class)]
    public function __construct(
        public int $userId,
        public string $type,
        public string $title,
        public string $message,
        public array $data = [],
        public string $priority = 'medium',
        public array $channels = ['browser'],
        public ?string $actionUrl = null,
        public ?string $actionText = null,
    ) {}

    public function validate(NotificationState $state)
    {
        // Validate user preferences allow this notification type
        if (!empty($state->preferences)) {
            $typeKey = str_replace('_', '', $this->type);
            if (isset($state->preferences[$typeKey]) && !$state->preferences[$typeKey]) {
                $this->assert(false, "User has disabled {$this->type} notifications");
            }
        }
    }

    public function apply(NotificationState $state)
    {
        // Add the notification to the state
        $state->addNotification([
            'id' => uniqid(),
            'type' => $this->type,
            'title' => $this->title,
            'message' => $this->message,
            'data' => $this->data,
            'priority' => $this->priority,
            'channels' => $this->channels,
            'action_url' => $this->actionUrl,
            'action_text' => $this->actionText,
            'requested_at' => now()->toISOString(),
            'status' => 'pending'
        ]);
    }

    public function handle()
    {
        // Project to Laravel's notifications table if needed
        // This is the "write model" projection
    }
}
