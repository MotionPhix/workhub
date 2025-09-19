<?php

namespace App\Events;

use App\States\NotificationState;
use Thunk\Verbs\Attributes\Autodiscovery\StateId;
use Thunk\Verbs\Event;

class NotificationPreferencesUpdated extends Event
{
    #[StateId(NotificationState::class)]
    public function __construct(
        public int $userId,
        public array $preferences,
    ) {}

    public function apply(NotificationState $state)
    {
        $state->updatePreferences($this->preferences);
    }

    public function handle()
    {
        // Update database preferences table
        // This handles the "read model" projection
    }
}
