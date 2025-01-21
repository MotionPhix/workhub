<?php

namespace App\Events;

use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserLoggedIn
{
  use Dispatchable, InteractsWithSockets, SerializesModels;

  public $user;
  public $ipAddress;
  public $userAgent;

  public function __construct(User $user)
  {
    $this->user = $user;
    $this->ipAddress = request()->ip();
    $this->userAgent = request()->userAgent();
  }

  public function broadcastOn(): array
  {
    return [
      new PrivateChannel('channel-name'),
    ];
  }
}
