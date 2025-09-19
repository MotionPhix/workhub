<?php

namespace App\Events;

use App\States\NotificationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class NotificationSent extends Event
{
    #[StateId(NotificationState::class)]
    public function __construct(
        public int $userId,
        public string $notificationId,
        public string $channel,
        public bool $success = true,
        public ?string $errorMessage = null,
    ) {}

    public function apply(NotificationState $state)
    {
        if ($this->success) {
            $state->markAsSent($this->notificationId, $this->channel);
        } else {
            $state->markAsFailed($this->notificationId, $this->channel, $this->errorMessage);
        }
    }

    public function handle()
    {
        // Update Laravel notifications table if needed
        // This handles the "read model" projection
    }
}
