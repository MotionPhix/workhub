<?php

namespace App\Events;

use App\States\NotificationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class NotificationRead extends Event
{
    #[StateId(NotificationState::class)]
    public function __construct(
        public int $userId,
        public string $notificationId,
    ) {}

    public function apply(NotificationState $state)
    {
        $state->markAsRead($this->notificationId);
    }

    public function handle()
    {
        // Update Laravel notifications table read_at timestamp
        // This handles the "read model" projection
    }
}
