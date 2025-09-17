<?php

namespace App\Jobs;

use App\Models\UserInvite;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class SendUserInviteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public UserInvite $invitation
    ) {}

    public function handle(): void
    {
        // Send invitation email
        // This is a placeholder - you would implement the actual email sending logic here
        // For now, we'll just mark the invitation as sent
        $this->invitation->update(['status' => 'pending']);
    }
}
